<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Reservasi;
use App\Models\ReservasiItem;
use App\Models\Resto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OwnerController extends Controller
{
    // Opsi filter di halaman reservasi masuk (Tahap 3)
    private const STATUS_RESERVASI = ['menunggu', 'dikonfirmasi', 'check_in', 'selesai', 'ditolak', 'dibatalkan'];
    private const STATUS_DP        = ['belum_bayar', 'menunggu_verifikasi', 'diterima', 'ditolak'];

    /* =====================================================================
     |  HELPER (Tahap 1)
     | ===================================================================== */

    // Pemilik hanya melihat resto miliknya. Admin melihat semua resto (read-only).
    private function restoScope()
    {
        $user = auth()->user();

        return $user->isAdmin() ? Resto::query() : $user->restos();
    }

    // Dipakai untuk aksi tulis resto/menu: hanya pemilik sebenarnya.
    private function authorizeResto(Resto $resto): void
    {
        abort_unless($resto->pemilik_id === auth()->id(), 403);
    }

    // Pastikan reservasi ini milik salah satu resto yang boleh diakses user.
    private function authorizeReservasi(Reservasi $reservasi): void
    {
        abort_unless($this->restoScope()->whereKey($reservasi->resto_id)->exists(), 403);
    }

    /* =====================================================================
     |  ATURAN VALIDASI (Tahap 2: dipakai bersama oleh store & update)
     | ===================================================================== */

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

    /* =====================================================================
     |  DASHBOARD  (Tahap 5)
     | ===================================================================== */

    // "Hari ini" mengikuti WIB, bukan zona waktu server (agar tidak bergeser setelah pukul 17.00 WIB)
    private function hariIni()
    {
        return now('Asia/Jakarta')->startOfDay();
    }

    public function dashboard()
    {
        // Samakan data dengan sisi pelanggan: batalkan reservasi yang lewat batas upload DP
        Reservasi::batalkanKedaluwarsa();

        $restos   = $this->restoScope()->withCount('menus')->get();
        $restoIds = $restos->pluck('id');
        $hariIni  = $this->hariIni();

        // Reservasi yang kunjungannya hari ini (yang ditolak/dibatalkan tidak perlu ditampilkan)
        $reservasisHariIni = Reservasi::whereIn('resto_id', $restoIds)
            ->whereDate('tanggal', $hariIni->toDateString())
            ->whereNotIn('status', ['ditolak', 'dibatalkan'])
            ->with(['user', 'resto', 'items.menu'])
            ->orderBy('jam')
            ->get();

        $menunggu = Reservasi::whereIn('resto_id', $restoIds)->where('status', 'menunggu')->count();

        $perluVerifikasi = Reservasi::whereIn('resto_id', $restoIds)
            ->where('status_dp', 'menunggu_verifikasi')
            ->whereIn('status', Reservasi::STATUS_AKTIF)
            ->count();

        return view('owner.dashboard', compact('restos', 'reservasisHariIni', 'menunggu', 'perluVerifikasi', 'hariIni'));
    }

    // Data grafik: jumlah reservasi per tanggal kunjungan, 6 hari lalu s/d 7 hari ke depan
    public function chartData()
    {
        $restoIds = $this->restoScope()->pluck('id');
        $hari     = $this->hariIni();
        $awal     = $hari->copy()->subDays(6);
        $akhir    = $hari->copy()->addDays(7);

        $rows = DB::table('reservasis')
            ->whereIn('resto_id', $restoIds)
            ->whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()])
            ->whereNotIn('status', ['ditolak', 'dibatalkan'])
            ->selectRaw('tanggal, COUNT(*) as total')
            ->groupBy('tanggal')
            ->get()
            ->mapWithKeys(fn ($r) => [substr((string) $r->tanggal, 0, 10) => (int) $r->total]);

        $labels = [];
        $data   = [];
        $idxHariIni = 0;

        for ($d = $awal->copy(); $d->lte($akhir); $d->addDay()) {
            $kunci    = $d->toDateString();
            $labels[] = $d->copy()->locale('id')->translatedFormat('d M');
            $data[]   = $rows[$kunci] ?? 0;

            if ($kunci === $hari->toDateString()) {
                $idxHariIni = count($labels) - 1;
            }
        }

        return response()->json(['labels' => $labels, 'data' => $data, 'hariIni' => $idxHariIni]);
    }

    /* =====================================================================
     |  RESERVASI MASUK  (diisi di Tahap 3)
     | ===================================================================== */

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

        // Cari nama pelanggan atau kode booking (4 karakter terakhir kode, mis. RV-171026-AB12)
        if ($request->filled('q')) {
            $q    = mb_strtolower(trim($request->q));
            $kode = Str::afterLast($q, '-');

            $query->where(function ($w) use ($q, $kode) {
                $w->whereHas('user', fn ($u) => $u->whereRaw('LOWER(name) LIKE ?', ["%{$q}%"]))
                  ->when(strlen($kode) >= 3, fn ($x) => $x->orWhereRaw('CAST(id AS TEXT) LIKE ?', ['%'.$kode]));
            });
        }

        $reservasis = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        // Ringkasan jumlah per status (tidak terpengaruh filter)
        $hitung = Reservasi::whereIn('resto_id', $restoIds)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Bukti DP yang menunggu keputusan pemilik
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

    /* =====================================================================
     |  RESTO  (Tahap 2)
     | ===================================================================== */

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
            unset($data['foto']); // jangan menimpa foto lama dengan null
        }

        $resto->update($data);

        return back()->with('success', 'Data restoran berhasil diperbarui.');
    }

    // Aktif/nonaktif resto (libur sementara). Resto nonaktif tidak muncul di pencarian pelanggan.
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

        // Jangan hapus resto yang masih punya tamu yang akan datang
        $aktif = $resto->reservasis()->whereIn('status', Reservasi::STATUS_AKTIF)->count();

        if ($aktif > 0) {
            return back()->with('error', "{$resto->nama} masih punya {$aktif} reservasi aktif. "
                .'Selesaikan atau tolak dulu reservasinya, atau nonaktifkan sementara saja.');
        }

        // Bersihkan file foto resto dan foto menu-nya
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

    /* =====================================================================
     |  MENU  (Tahap 2)
     | ===================================================================== */

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

        // Form mengirim hidden "0" + checkbox "1", jadi menu baru tidak otomatis Habis
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

        // reservasi_items.menu_id tidak cascade: menu yang pernah dipesan tidak boleh dihapus
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

    /* =====================================================================
     |  STATUS RESERVASI & VERIFIKASI DP  (Tahap 4)
     | ===================================================================== */

    // Alur: menunggu -> dikonfirmasi -> check_in -> selesai.
    // Tolak hanya sebelum check-in. "lunas" hanya mengubah status pembayaran.
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

        // Status asal yang boleh untuk tiap aksi
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

            default: // lunas
                $reservasi->status_pembayaran = 'lunas';
                $pesan = 'Pembayaran ditandai lunas.';
        }

        $reservasi->save();

        return back()->with('success', $pesan);
    }

    // Verifikasi bukti transfer DP: terima atau tolak (tolak wajib alasan)
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
            $reservasi->alasan_penolakan = null; // bersihkan alasan DP lama (jika pernah ditolak)
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
