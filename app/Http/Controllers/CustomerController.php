<?php

namespace App\Http\Controllers;

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
        $reservasis = $request->user()->reservasis()->with(['resto', 'items.menu'])->latest()->get();
        return view('customer.reservations', compact('reservasis'));
    }

    public function uploadProof(Request $request, Reservasi $reservasi)
    {
        abort_unless($reservasi->user_id === $request->user()->id, 403);
        abort_unless($reservasi->status_dp !== 'diterima', 422, 'DP sudah diverifikasi.');
        $data = $request->validate(['bukti_dp' => 'required|image|max:4096']);
        if ($reservasi->bukti_dp) \Illuminate\Support\Facades\Storage::disk('public')->delete($reservasi->bukti_dp);
        $reservasi->bukti_dp = $request->file('bukti_dp')->store('bukti-dp', 'public');
        $reservasi->status_dp = 'menunggu_verifikasi';
        $reservasi->save();
        return back()->with('success', 'Bukti DP berhasil diunggah dan menunggu verifikasi pemilik resto.');
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
