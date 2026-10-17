<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Models\Menu;
use App\Models\Reservasi;
use App\Models\ReservasiItem;
use App\Models\Resto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $tanggal = $request->input('tanggal', now()->toDateString());
        $jam = $request->input('jam', '19:00');
        $jumlah = max(1, (int) $request->input('jumlah_orang', 2));
        $query = Resto::query()->where('is_active', true)->with('menus')->when($request->filled('q'), fn ($q) => $q->where('nama', 'like', '%'.$request->q.'%'))
            ->when($request->filled('kota'), fn ($q) => $q->where('kota', $request->kota))
            ->when($request->filled('harga'), function ($q) use ($request) {
                [$min, $max] = array_pad(explode('-', $request->harga), 2, 999999999);
                $q->whereHas('menus', fn ($m) => $m->whereBetween('harga', [(int)$min, (int)$max]));
            });
        $restos = $query->get()->map(function ($resto) use ($tanggal, $jam) {
            $resto->sisa_slot = $resto->sisaKapasitas($tanggal, $jam);
            $resto->availability = $resto->statusKetersediaan($tanggal, $jam);
            $resto->harga_min = $resto->menus->where('tersedia', true)->min('harga') ?? 0;
            $resto->harga_max = $resto->menus->where('tersedia', true)->max('harga') ?? 0;
            return $resto;
        })->filter(fn ($resto) => $resto->sisa_slot >= (int)$jumlah)->values();
        $kota = Resto::where('is_active', true)->select('kota')->distinct()->orderBy('kota')->pluck('kota');
        return view('customer.index', compact('restos', 'tanggal', 'jam', 'jumlah', 'kota'));
    }

    public function show(Resto $resto)
    {
        abort_unless($resto->is_active, 404);
        $resto->load(['menus' => fn ($q) => $q->orderBy('kategori')->orderBy('nama')]);
        return view('customer.show', compact('resto'));
    }

    public function profile()
    {
        return view('customer.profile', ['user' => auth()->user(), 'upcoming' => auth()->user()->reservasis()->with('resto')->latest()->first()]);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'no_hp' => 'required|string|max:20', 'password' => 'nullable|string|min:8|confirmed']);
        $user = $request->user();
        $user->name = $data['name'];
        $user->no_hp = $data['no_hp'];
        if (!empty($data['password'])) $user->password = $data['password'];
        $user->save();
        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function reservations(Request $request)
    {
        Reservasi::batalkanKedaluwarsa();

        $user  = $request->user();
        $aktif = ['menunggu', 'dikonfirmasi', 'check_in'];
        $batal = ['dibatalkan', 'ditolak'];

        $hitung = [
            'semua'   => $user->reservasis()->count(),
            'aktif'   => $user->reservasis()->whereIn('status', $aktif)->count(),
            'selesai' => $user->reservasis()->where('status', 'selesai')->count(),
            'batal'   => $user->reservasis()->whereIn('status', $batal)->count(),
        ];

        $tab   = $request->input('tab', 'semua');
        $query = $user->reservasis()->with('resto');

        if ($tab === 'aktif') {
            $query->whereIn('status', $aktif);
        } elseif ($tab === 'selesai') {
            $query->where('status', 'selesai');
        } elseif ($tab === 'batal') {
            $query->whereIn('status', $batal);
        }

        // Cari kode booking (4 karakter terakhir) atau nama resto
        if ($request->filled('q')) {
            $q    = mb_strtolower(trim($request->input('q')));
            $kode = Str::afterLast($q, '-');

            $query->where(function ($w) use ($q, $kode) {
                $w->whereHas('resto', fn ($r) => $r->whereRaw('LOWER(nama) LIKE ?', ["%{$q}%"]))
                ->when(strlen($kode) >= 3, fn ($x) => $x->orWhereRaw('CAST(id AS TEXT) LIKE ?', ['%'.$kode]));
            });
        }

        if ($request->filled('periode')) {
            try {
                $awal = Carbon::createFromFormat('!Y-m', $request->input('periode'));
                $query->whereBetween('tanggal', [
                    $awal->copy()->startOfMonth()->toDateString(),
                    $awal->copy()->endOfMonth()->toDateString(),
                ]);
            } catch (\Throwable $e) {
                // format periode tidak valid, abaikan filter
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('dp')) {
            $query->where('status_dp', $request->input('dp'));
        }

        $reservasis = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        return view('customer.reservations', compact('reservasis', 'hitung', 'tab'));
    }

    public function uploadProof(Request $request, Reservasi $reservasi)
    {
        abort_unless($reservasi->user_id === $request->user()->id, 403);

        if (! in_array($reservasi->status, ['menunggu', 'dikonfirmasi'], true)) {
            return back()->with('error', 'Reservasi ini sudah tidak aktif, bukti DP tidak bisa diunggah.');
        }

        if ($reservasi->status_dp === 'diterima') {
            return back()->with('error', 'DP sudah diverifikasi oleh resto.');
        }

        $request->validate(['bukti_dp' => 'required|image|max:4096'], [
            'bukti_dp.required' => 'Pilih file bukti transfer terlebih dahulu.',
            'bukti_dp.image'    => 'Bukti harus berupa gambar (JPG atau PNG).',
            'bukti_dp.max'      => 'Ukuran gambar maksimal 4 MB.',
        ]);

        if ($reservasi->bukti_dp) {
            Storage::disk('public')->delete($reservasi->bukti_dp);
        }

        $reservasi->bukti_dp  = $request->file('bukti_dp')->store('bukti-dp', 'public');
        $reservasi->status_dp = 'menunggu_verifikasi';
        $reservasi->save();

        return back()->with('success', 'Bukti DP berhasil diunggah dan menunggu verifikasi pemilik resto.');
    }

    public function showReservation(Request $request, Reservasi $reservasi)
    {
        abort_unless($reservasi->user_id === $request->user()->id, 403);

        Reservasi::batalkanKedaluwarsa();
        $reservasi->refresh()->load(['resto', 'user', 'items.menu']);

        $menus = $reservasi->resto->menus()->orderBy('kategori')->orderBy('nama')->get();
        $qty   = $reservasi->items->pluck('jumlah', 'menu_id');

        return view('customer.reservations-detail', compact('reservasi', 'menus', 'qty'));
    }

    public function updateReservation(Request $request, Reservasi $reservasi)
    {
        abort_unless($reservasi->user_id === $request->user()->id, 403);

        if (! $reservasi->bisa_diedit) {
            return back()->with('error', 'Reservasi ini sudah dikonfirmasi atau selesai, jadi tidak bisa diubah lagi.');
        }

        $data = $request->validate([
            'tanggal'      => 'required|date|after_or_equal:today',
            'jam'          => 'required|date_format:H:i',
            'jumlah_orang' => 'required|integer|min:1|max:100',
            'menu'         => 'required|array',
            'menu.*'       => 'nullable|integer|min:0|max:50',
        ], [
            'tanggal.required'       => 'Tanggal kunjungan wajib diisi.',
            'tanggal.after_or_equal' => 'Tanggal kunjungan tidak boleh sebelum hari ini.',
            'jam.required'           => 'Jam kunjungan wajib diisi.',
            'jam.date_format'        => 'Format jam tidak valid.',
            'jumlah_orang.required'  => 'Jumlah orang wajib diisi.',
            'jumlah_orang.min'       => 'Jumlah orang minimal 1.',
            'jumlah_orang.max'       => 'Jumlah orang maksimal 100.',
            'menu.required'          => 'Pilih minimal satu menu.',
            'menu.*.integer'         => 'Kuantitas menu harus berupa angka.',
            'menu.*.max'             => 'Kuantitas satu menu maksimal 50.',
        ]);

        $resto   = $reservasi->resto;
        $dipesan = collect($data['menu'])->map(fn ($q) => (int) $q)->filter(fn ($q) => $q > 0);

        if ($dipesan->isEmpty()) {
            return back()->withErrors(['menu' => 'Pilih minimal satu menu dengan kuantitas lebih dari 0.'])->withInput();
        }

        if ($dipesan->keys()->contains(fn ($id) => ! Str::isUuid($id))) {
            return back()->withErrors(['menu' => 'Ada menu yang tidak valid.'])->withInput();
        }

        $menus = $resto->menus()->whereIn('id', $dipesan->keys())->get()->keyBy('id');

        if ($menus->count() !== $dipesan->count()) {
            return back()->withErrors(['menu' => 'Ada menu yang tidak valid.'])->withInput();
        }

        // Menu yang sudah habis boleh dikurangi, tapi tidak boleh ditambah
        $lama = $reservasi->items()->pluck('jumlah', 'menu_id');

        foreach ($dipesan as $id => $qty) {
            if (! $menus[$id]->tersedia && $qty > (int) ($lama[$id] ?? 0)) {
                return back()->withErrors(['menu' => "Menu {$menus[$id]->nama} sedang habis dan tidak bisa ditambah."])->withInput();
            }
        }

        // Cek kapasitas, tanpa menghitung reservasi ini sendiri
        $terpakai = (int) $resto->reservasis()
            ->where('tanggal', $data['tanggal'])
            ->where('jam', $data['jam'])
            ->whereIn('status', Reservasi::STATUS_AKTIF)
            ->where('id', '!=', $reservasi->id)
            ->sum('jumlah_orang');

        $sisa = $resto->kapasitas - $terpakai;

        if ($data['jumlah_orang'] > $sisa) {
            return back()->withErrors(['jumlah_orang' => 'Kapasitas tidak mencukupi pada jadwal tersebut. Sisa kursi: '.max(0, $sisa).'.'])->withInput();
        }

        // Cek minimal pesan: Rp50.000 per orang
        // Cek minimal pesan: Rp50.000 total
        $total   = $dipesan->map(fn ($q, $id) => $menus[$id]->harga * $q)->sum();
        $minimum = Reservasi::MINIMAL_PER_ORANG; // 50000

        if ($total < $minimum) {
            return back()->withErrors(['menu' => 'Total pesanan minimal Rp'.number_format($minimum, 0, ',', '.').'.'])->withInput();
        }

        $dpBaru   = (int) round($total * $resto->dp_persen / 100);
        $statusDp = $reservasi->status_dp;
        $dpBerubah = $dpBaru !== (int) $reservasi->dp_nominal;

        // Kalau nominal DP berubah dan bukti sudah ada, resto perlu verifikasi ulang
        if ($dpBerubah && in_array($statusDp, ['menunggu_verifikasi', 'diterima'], true)) {
            $statusDp = 'menunggu_verifikasi';
        }

        DB::transaction(function () use ($reservasi, $data, $total, $dpBaru, $statusDp, $dipesan, $menus) {
            $reservasi->update([
                'tanggal'      => $data['tanggal'],
                'jam'          => $data['jam'],
                'jumlah_orang' => $data['jumlah_orang'],
                'total_harga'  => $total,
                'dp_nominal'   => $dpBaru,
                'status_dp'    => $statusDp,
            ]);

            $reservasi->items()->delete();

            foreach ($dipesan as $id => $qty) {
                $reservasi->items()->create([
                    'menu_id'      => $id,
                    'jumlah'       => $qty,
                    'harga_satuan' => $menus[$id]->harga,
                ]);
            }
        });

        $pesan = 'Reservasi berhasil diperbarui.';

        if ($dpBerubah && $statusDp === 'menunggu_verifikasi') {
            $pesan .= ' Nominal DP berubah, resto akan memverifikasi ulang bukti Anda.';
        } elseif ($dpBerubah && $reservasi->status_dp !== 'diterima') {
            $pesan .= ' Nominal DP baru: Rp'.number_format($dpBaru, 0, ',', '.').'.';
        }

        return redirect()->route('customer.reservations.show', $reservasi)->with('success', $pesan);
    }

// Delete = batalkan reservasi (riwayatnya tetap tersimpan lewat status)
    public function cancelReservation(Request $request, Reservasi $reservasi)
    {
        abort_unless($reservasi->user_id === $request->user()->id, 403);

        if (! $reservasi->bisa_dibatalkan) {
            return back()->with('error', 'Reservasi ini tidak bisa dibatalkan (sudah check-in, selesai, atau sudah dibatalkan).');
        }

        $reservasi->update(['status' => 'dibatalkan']);

        return redirect()->route('customer.reservations')->with('success', 'Reservasi berhasil dibatalkan.');
    }

    public function createReservation(Resto $resto)
    {
        abort_unless($resto->is_active, 404);
        $resto->load(['menus' => fn ($q) => $q->where('tersedia', true)]);
        return view('customer.reserve', compact('resto'));
    }

    public function storeReservation(Request $request, Resto $resto)
    {
        $data = $request->validate([
            'tanggal' => 'required|date|after_or_equal:today', 'jam' => 'required|date_format:H:i',
            'jumlah_orang' => 'required|integer|min:1|max:100', 'catatan' => 'nullable|string|max:1000',
            'menu' => 'required|array|min:1', 'menu.*' => 'nullable|integer|min:0|max:50',
        ]);
        $menus = $resto->menus()->where('tersedia', true)->whereIn('id', array_keys(array_filter($data['menu'])))->get()->keyBy('id');
        if ($menus->count() !== count(array_filter($data['menu']))) return back()->withErrors(['menu' => 'Ada menu yang tidak valid atau sudah habis.'])->withInput();
        if ($resto->sisaKapasitas($data['tanggal'], $data['jam']) < $data['jumlah_orang']) return back()->withErrors(['jumlah_orang' => 'Kapasitas tidak mencukupi pada jadwal tersebut.'])->withInput();
        $total = 0;
        foreach ($data['menu'] as $id => $qty) if ((int)$qty > 0) $total += $menus[$id]->harga * (int)$qty;
        if ($total < 50000) return back()->withErrors(['menu' => 'Minimal total pesanan menu Rp50.000.'])->withInput();
        $dp = (int) round($total * $resto->dp_persen / 100);
        $reservation = DB::transaction(function () use ($request, $resto, $data, $menus, $total, $dp) {
            $reservation = Reservasi::create([
                'user_id' => $request->user()->id, 'resto_id' => $resto->id, 'tanggal' => $data['tanggal'], 'jam' => $data['jam'],
                'jumlah_orang' => $data['jumlah_orang'], 'catatan' => $data['catatan'] ?? null, 'status' => 'menunggu',
                'total_harga' => $total, 'dp_nominal' => $dp, 'status_dp' => 'belum_bayar', 'status_pembayaran' => 'belum_lunas',
            ]);
            foreach ($data['menu'] as $id => $qty) if ((int)$qty > 0) ReservasiItem::create(['reservasi_id' => $reservation->id, 'menu_id' => $id, 'jumlah' => (int)$qty, 'harga_satuan' => $menus[$id]->harga]);
            return $reservation;
        });
        return redirect()->route('customer.reservations')->with('success', 'Reservasi dibuat. Kode booking: '.$reservation->id);
    }
}
