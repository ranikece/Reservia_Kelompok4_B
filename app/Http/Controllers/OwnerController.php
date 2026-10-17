<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Reservasi;
use App\Models\ReservasiItem;
use App\Models\Resto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OwnerController extends Controller
{
    private const STATUS_RESERVASI = ['menunggu', 'dikonfirmasi', 'check_in', 'selesai', 'ditolak', 'dibatalkan'];
    private const STATUS_DP        = ['belum_bayar', 'menunggu_verifikasi', 'diterima', 'ditolak'];

    private function restoScope()
    {
        $user = auth()->user();

        return $user->isAdmin() ? Resto::query() : $user->restos();
    }

    private function authorizeResto(Resto $resto): void
    {
        abort_unless($resto->pemilik_id === auth()->id(), 403);
    }

    private function authorizeReservasi(Reservasi $reservasi): void
    {
        abort_unless($this->restoScope()->whereKey($reservasi->resto_id)->exists(), 403);
    }

    private function restoRules(): array
    {
        return [
            'nama'        => 'required|string|max:150',
            'alamat'      => 'required|string|max:500',
            'kota'        => 'required|string|max:100',
            'kapasitas'   => 'required|integer|min:1|max:10000',
            'jam_buka'    => 'required|date_format:H:i',
            'jam_tutup'   => 'required|date_format:H:i',
            'fasilitas'   => 'nullable|string|max:1000',
            'foto'        => 'nullable|image|max:4096',
            'dp_persen'   => 'required|integer|min:0|max:100',
            'nama_bank'   => 'nullable|string|max:100',
            'no_rekening' => 'nullable|string|max:50',
            'atas_nama'   => 'nullable|string|max:150',
        ];
    }

    private function restoMessages(): array
    {
        return [
            'nama.required'         => 'Nama restoran wajib diisi.',
            'alamat.required'       => 'Alamat wajib diisi.',
            'kota.required'         => 'Kota wajib diisi.',
            'kapasitas.required'    => 'Kapasitas kursi wajib diisi.',
            'kapasitas.min'         => 'Kapasitas minimal 1 kursi.',
            'jam_buka.required'     => 'Jam buka wajib diisi.',
            'jam_buka.date_format'  => 'Format jam buka tidak valid.',
            'jam_tutup.required'    => 'Jam tutup wajib diisi.',
            'jam_tutup.date_format' => 'Format jam tutup tidak valid.',
            'dp_persen.required'    => 'Persen DP wajib diisi.',
            'dp_persen.max'         => 'Persen DP maksimal 100.',
            'foto.image'            => 'Foto harus berupa gambar (JPG atau PNG).',
            'foto.max'              => 'Ukuran foto maksimal 4 MB.',
        ];
    }

    private function menuRules(): array
    {
        return [
            'nama'     => 'required|string|max:150',
            'kategori' => 'required|string|max:80',
            'harga'    => 'required|integer|min:0',
            'foto'     => 'nullable|image|max:4096',
            'tersedia' => 'nullable|boolean',
        ];
    }

    private function menuMessages(): array
    {
        return [
            'nama.required'     => 'Nama menu wajib diisi.',
            'kategori.required' => 'Kategori menu wajib diisi.',
            'harga.required'    => 'Harga menu wajib diisi.',
            'harga.integer'     => 'Harga harus berupa angka bulat.',
            'foto.image'        => 'Foto menu harus berupa gambar (JPG atau PNG).',
            'foto.max'          => 'Ukuran foto menu maksimal 4 MB.',
        ];
    }

    public function dashboard()
    {
        Reservasi::batalkanKedaluwarsa();

        $restos = $this->restoScope()->withCount('menus')->get();
        $reservasis = Reservasi::whereIn('resto_id', $restos->pluck('id'))
            ->with(['user', 'resto', 'items.menu'])->latest()->get();

        return view('owner.dashboard', compact('restos', 'reservasis'));
    }

    public function chartData()
    {
        return response()->json(['labels' => [], 'data' => []]);
    }

    public function reservations(Request $request)
    {
        Reservasi::batalkanKedaluwarsa();

        $request->validate([
            'status'  => 'nullable|in:'.implode(',', self::STATUS_RESERVASI),
            'dp'      => 'nullable|in:'.implode(',', self::STATUS_DP),
            'tanggal' => 'nullable|date',
            'resto'   => 'nullable|uuid',
            'q'       => 'nullable|string|max:100',
        ]);

        $restos   = $this->restoScope()->orderBy('nama')->get(['id', 'nama']);
        $restoIds = $restos->pluck('id');

        $query = Reservasi::whereIn('resto_id', $restoIds)->with(['user', 'resto']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('dp')) {
            $query->where('status_dp', $request->dp);
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }

        if ($request->filled('resto')) {
            $query->where('resto_id', $request->resto);
        }

        if ($request->filled('q')) {
            $q    = mb_strtolower(trim($request->q));
            $kode = Str::afterLast($q, '-');

            $query->where(function ($w) use ($q, $kode) {
                $w->whereHas('user', fn ($u) => $u->whereRaw('LOWER(name) LIKE ?', ["%{$q}%"]))
                  ->when(strlen($kode) >= 3, fn ($x) => $x->orWhereRaw('CAST(id AS TEXT) LIKE ?', ['%'.$kode]));
            });
        }

        $reservasis = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        $hitung = Reservasi::whereIn('resto_id', $restoIds)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $perluVerifikasi = Reservasi::whereIn('resto_id', $restoIds)
            ->where('status_dp', 'menunggu_verifikasi')
            ->whereIn('status', Reservasi::STATUS_AKTIF)
            ->count();

        return view('owner.reservations', compact('reservasis', 'restos', 'hitung', 'perluVerifikasi'));
    }

    public function showReservation(Reservasi $reservasi)
    {
        $this->authorizeReservasi($reservasi);

        Reservasi::batalkanKedaluwarsa();
        $reservasi->refresh()->load(['user', 'resto', 'items.menu']);

        return view('owner.reservations-detail', compact('reservasi'));
    }

    public function restos()
    {
        $restos = $this->restoScope()
            ->with([
                'pemilik',
                'menus' => fn ($q) => $q->orderBy('kategori')->orderBy('nama'),
            ])
            ->withCount('menus')
            ->latest()
            ->get();

        return view('owner.restos', compact('restos'));
    }

    public function storeResto(Request $request)
    {
        $data = $request->validate($this->restoRules(), $this->restoMessages());
        $data['pemilik_id'] = $request->user()->id;

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('restos', 'public');
        }

        Resto::create($data);

        return back()->with('success', 'Restoran berhasil ditambahkan.');
    }

    public function updateResto(Request $request, Resto $resto)
    {
        $this->authorizeResto($resto);
        $data = $request->validate($this->restoRules(), $this->restoMessages());

        if ($request->hasFile('foto')) {
            if ($resto->foto) {
                Storage::disk('public')->delete($resto->foto);
            }
            $data['foto'] = $request->file('foto')->store('restos', 'public');
        } else {
            unset($data['foto']);
        }

        $resto->update($data);

        return back()->with('success', 'Data restoran berhasil diperbarui.');
    }

    public function toggleResto(Resto $resto)
    {
        $this->authorizeResto($resto);

        $resto->update(['is_active' => ! $resto->is_active]);

        return back()->with('success', $resto->is_active
            ? "{$resto->nama} kembali aktif dan muncul di pencarian pelanggan."
            : "{$resto->nama} dinonaktifkan sementara (libur) dan tidak muncul di pencarian.");
    }

    public function deleteResto(Resto $resto)
    {
        $this->authorizeResto($resto);

        Reservasi::batalkanKedaluwarsa();

        $aktif = $resto->reservasis()->whereIn('status', Reservasi::STATUS_AKTIF)->count();

        if ($aktif > 0) {
            return back()->with('error', "{$resto->nama} masih punya {$aktif} reservasi aktif. "
                .'Selesaikan atau tolak dulu reservasinya, atau nonaktifkan sementara saja.');
        }

        $resto->load('menus');
        foreach ($resto->menus as $menu) {
            if ($menu->foto) {
                Storage::disk('public')->delete($menu->foto);
            }
        }
        if ($resto->foto) {
            Storage::disk('public')->delete($resto->foto);
        }

        $resto->delete();

        return back()->with('success', 'Restoran berhasil dihapus.');
    }

    public function storeMenu(Request $request)
    {
        $data = $request->validate(
            ['resto_id' => 'required|uuid'] + $this->menuRules(),
            $this->menuMessages()
        );

        $resto = auth()->user()->restos()->findOrFail($data['resto_id']);

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('menus', 'public');
        }

        $data['tersedia'] = $request->boolean('tersedia');

        $resto->menus()->create($data);

        return back()->with('success', 'Menu berhasil ditambahkan.');
    }

    public function updateMenu(Request $request, Menu $menu)
    {
        $this->authorizeResto($menu->resto);
        $data = $request->validate($this->menuRules(), $this->menuMessages());

        if ($request->hasFile('foto')) {
            if ($menu->foto) {
                Storage::disk('public')->delete($menu->foto);
            }
            $data['foto'] = $request->file('foto')->store('menus', 'public');
        } else {
            unset($data['foto']);
        }

        $data['tersedia'] = $request->boolean('tersedia');
        $menu->update($data);

        return back()->with('success', 'Menu berhasil diperbarui.');
    }

    public function deleteMenu(Menu $menu)
    {
        $this->authorizeResto($menu->resto);

        if (ReservasiItem::where('menu_id', $menu->id)->exists()) {
            return back()->with('error', "Menu {$menu->nama} pernah dipesan pelanggan sehingga tidak bisa dihapus. "
                .'Ubah statusnya menjadi Habis agar tidak bisa dipesan lagi.');
        }

        if ($menu->foto) {
            Storage::disk('public')->delete($menu->foto);
        }

        $menu->delete();

        return back()->with('success', 'Menu berhasil dihapus.');
    }

    public function updateStatus(Request $request, Reservasi $reservasi)
    {
        $this->authorizeReservasi($reservasi);

        Reservasi::batalkanKedaluwarsa();
        $reservasi->refresh();

        $data = $request->validate(
            ['status' => 'required|in:dikonfirmasi,tolak,check_in,lunas,selesai'],
            ['status.required' => 'Aksi tidak valid.', 'status.in' => 'Aksi tidak valid.']
        );
        $aksi = $data['status'];

        $izin = [
            'dikonfirmasi' => ['menunggu'],
            'tolak'        => ['menunggu', 'dikonfirmasi'],
            'check_in'     => ['dikonfirmasi'],
            'selesai'      => ['check_in'],
            'lunas'        => ['dikonfirmasi', 'check_in', 'selesai'],
        ];

        if (! in_array($reservasi->status, $izin[$aksi], true)) {
            return back()->with('error', 'Aksi ini tidak bisa dilakukan pada reservasi berstatus "'.$reservasi->status_label.'".');
        }

        if ($aksi === 'lunas' && $reservasi->status_pembayaran === 'lunas') {
            return back()->with('error', 'Pembayaran reservasi ini sudah ditandai lunas.');
        }

        switch ($aksi) {
            case 'dikonfirmasi':
                $reservasi->status = 'dikonfirmasi';
                $pesan = 'Reservasi dikonfirmasi.';
                break;

            case 'tolak':
                $request->validate(
                    ['alasan_penolakan' => 'required|string|max:500'],
                    ['alasan_penolakan.required' => 'Alasan penolakan wajib diisi.']
                );
                $reservasi->status = 'ditolak';
                $reservasi->alasan_penolakan = $request->alasan_penolakan;
                $pesan = 'Reservasi ditolak. Alasan akan terlihat oleh pelanggan.';
                break;

            case 'check_in':
                $reservasi->status = 'check_in';
                $pesan = 'Pelanggan ditandai sudah check-in.';
                break;

            case 'selesai':
                $reservasi->status = 'selesai';
                $pesan = 'Reservasi selesai. Kursi kembali tersedia untuk jadwal tersebut.';
                break;

            default:
                $reservasi->status_pembayaran = 'lunas';
                $pesan = 'Pembayaran ditandai lunas.';
        }

        $reservasi->save();

        return back()->with('success', $pesan);
    }

    public function updateDpStatus(Request $request, Reservasi $reservasi)
    {
        $this->authorizeReservasi($reservasi);

        Reservasi::batalkanKedaluwarsa();
        $reservasi->refresh();

        $data = $request->validate([
            'status_dp'  => 'required|in:diterima,ditolak',
            'catatan_dp' => 'required_if:status_dp,ditolak|nullable|string|max:500',
        ], [
            'status_dp.required'      => 'Pilih terima atau tolak DP.',
            'status_dp.in'            => 'Aksi DP tidak valid.',
            'catatan_dp.required_if'  => 'Alasan penolakan DP wajib diisi.',
        ]);

        if (! in_array($reservasi->status, ['menunggu', 'dikonfirmasi'], true)) {
            return back()->with('error', 'DP tidak bisa diverifikasi karena reservasi sudah tidak aktif.');
        }

        if ($reservasi->status_dp !== 'menunggu_verifikasi' || ! $reservasi->bukti_dp) {
            return back()->with('error', 'Tidak ada bukti DP yang menunggu verifikasi pada reservasi ini.');
        }

        if ($data['status_dp'] === 'diterima') {
            $reservasi->status_dp = 'diterima';
            $reservasi->alasan_penolakan = null;
            $pesan = 'DP diterima dan ditandai terverifikasi.';
        } else {
            $reservasi->status_dp = 'ditolak';
            $reservasi->alasan_penolakan = $data['catatan_dp'];
            $pesan = 'DP ditolak. Pelanggan dapat mengunggah ulang bukti transfer.';
        }

        $reservasi->save();

        return back()->with('success', $pesan);
    }
}