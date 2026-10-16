<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Reservasi;
use App\Models\Resto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OwnerController extends Controller
{
    public function dashboard()
    {
        $restos = auth()->user()->restos()->withCount('menus')->get();
        $reservasis = Reservasi::whereIn('resto_id', $restos->pluck('id'))
            ->with(['user', 'resto', 'items.menu'])->latest()->get();
        return view('owner.dashboard', compact('restos', 'reservasis'));
    }

    public function restos()
    {
        $restos = auth()->user()->restos()->withCount('menus')->latest()->get();
        return view('owner.restos', compact('restos'));
    }

    public function storeResto(Request $request)
    {
        $data = $request->validate([
            'nama'=>'required|string|max:150','alamat'=>'required|string|max:500','kota'=>'required|string|max:100',
            'kapasitas'=>'required|integer|min:1|max:10000','jam_buka'=>'required','jam_tutup'=>'required',
            'fasilitas'=>'nullable|string|max:1000','foto'=>'nullable|image|max:4096','dp_persen'=>'required|integer|min:0|max:100',
            'nama_bank'=>'nullable|string|max:100','no_rekening'=>'nullable|string|max:50','atas_nama'=>'nullable|string|max:150',
        ]);
        $data['pemilik_id'] = $request->user()->id;
        if ($request->hasFile('foto')) $data['foto'] = $request->file('foto')->store('restos','public');
        Resto::create($data);
        return back()->with('success','Restoran berhasil ditambahkan.');
    }

    public function updateResto(Request $request, Resto $resto)
    {
        $this->authorizeResto($resto);
        $data = $request->validate([
            'nama'=>'required|string|max:150','alamat'=>'required|string|max:500','kota'=>'required|string|max:100',
            'kapasitas'=>'required|integer|min:1|max:10000','jam_buka'=>'required','jam_tutup'=>'required',
            'fasilitas'=>'nullable|string|max:1000','foto'=>'nullable|image|max:4096','dp_persen'=>'required|integer|min:0|max:100',
            'nama_bank'=>'nullable|string|max:100','no_rekening'=>'nullable|string|max:50','atas_nama'=>'nullable|string|max:150',
        ]);
        if ($request->hasFile('foto')) {
            if ($resto->foto) Storage::disk('public')->delete($resto->foto);
            $data['foto'] = $request->file('foto')->store('restos','public');
        }
        $resto->update($data);
        return back()->with('success','Data restoran berhasil diperbarui.');
    }

    public function deleteResto(Resto $resto)
    {
        $this->authorizeResto($resto);
        $resto->delete();
        return back()->with('success','Restoran berhasil dihapus.');
    }

    public function storeMenu(Request $request)
    {
        $data = $request->validate(['resto_id'=>'required|uuid','nama'=>'required|string|max:150','kategori'=>'required|string|max:80','harga'=>'required|integer|min:0','foto'=>'nullable|image|max:4096','tersedia'=>'nullable|boolean']);
        $resto = auth()->user()->restos()->findOrFail($data['resto_id']);
        if ($request->hasFile('foto')) $data['foto'] = $request->file('foto')->store('menus','public');
        $data['tersedia'] = $request->boolean('tersedia');
        $resto->menus()->create($data);
        return back()->with('success','Menu berhasil ditambahkan.');
    }

    public function updateMenu(Request $request, Menu $menu)
    {
        $this->authorizeResto($menu->resto);
        $data = $request->validate(['nama'=>'required|string|max:150','kategori'=>'required|string|max:80','harga'=>'required|integer|min:0','foto'=>'nullable|image|max:4096','tersedia'=>'nullable|boolean']);
        if ($request->hasFile('foto')) {
            if ($menu->foto) Storage::disk('public')->delete($menu->foto);
            $data['foto'] = $request->file('foto')->store('menus','public');
        }
        $data['tersedia'] = $request->boolean('tersedia');
        $menu->update($data);
        return back()->with('success','Menu berhasil diperbarui.');
    }

    public function deleteMenu(Menu $menu)
    {
        $this->authorizeResto($menu->resto);
        $menu->delete();
        return back()->with('success','Menu berhasil dihapus.');
    }

    public function updateStatus(Request $request, Reservasi $reservasi)
    {
        abort_unless(auth()->user()->restos()->whereKey($reservasi->resto_id)->exists(), 403);
        $data = $request->validate(['status'=>'required|in:dikonfirmasi,tolak,check_in,lunas,selesai']);
        if ($data['status'] === 'tolak') {
            $request->validate(['alasan_penolakan'=>'required|string|max:500']);
            $reservasi->status = 'ditolak';
            $reservasi->alasan_penolakan = $request->alasan_penolakan;
        } elseif ($data['status'] === 'lunas') {
            $reservasi->status_pembayaran = 'lunas';
        } else {
            $reservasi->status = $data['status'];
        }
        $reservasi->save();
        return back()->with('success','Status reservasi diperbarui.');
    }

    public function updateDpStatus(Request $request, Reservasi $reservasi)
    {
        abort_unless(auth()->user()->restos()->whereKey($reservasi->resto_id)->exists(), 403);
        $data = $request->validate(['status_dp' => 'required|in:diterima,ditolak', 'catatan_dp' => 'nullable|string|max:500']);
        if ($data['status_dp'] === 'diterima') {
            abort_unless($reservasi->bukti_dp, 422, 'Pelanggan belum mengunggah bukti DP.');
            $reservasi->status_dp = 'diterima';
        } else {
            $reservasi->status_dp = 'ditolak';
        }
        if (!empty($data['catatan_dp'])) $reservasi->alasan_penolakan = $data['catatan_dp'];
        $reservasi->save();
        return back()->with('success', 'Status verifikasi DP diperbarui.');
    }

    private function authorizeResto(Resto $resto): void
    {
        abort_unless($resto->pemilik_id === auth()->id(), 403);
    }
}
