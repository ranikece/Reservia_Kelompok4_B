@extends('layouts.app')

@section('title', 'Riwayat reservasi')

@section('content')
    @php
        $rupiah = fn ($n) => 'Rp'.number_format($n, 0, ',', '.');
        $tabs = ['semua' => 'Semua', 'aktif' => 'Aktif', 'selesai' => 'Selesai', 'batal' => 'Dibatalkan'];
        $opsiStatus = [
            'menunggu' => 'Menunggu konfirmasi', 'dikonfirmasi' => 'Dikonfirmasi', 'check_in' => 'Sudah check-in',
            'selesai' => 'Selesai', 'ditolak' => 'Ditolak resto', 'dibatalkan' => 'Dibatalkan',
        ];
        $opsiDp = [
            'belum_bayar' => 'Belum bayar', 'menunggu_verifikasi' => 'Menunggu verifikasi',
            'diterima' => 'Terverifikasi', 'ditolak' => 'Ditolak',
        ];
    @endphp

    <div class="page">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div>
                <h1 class="mb-1">Riwayat reservasi</h1>
                <p class="subtitle mb-0">Semua rencana makan Anda, beserta status reservasi dan pembayaran yang terpisah.</p>
            </div>
            <a href="{{ route('customer.index') }}" class="btn btn-purple">
                <i class="bi bi-plus-lg me-1"></i> Cari resto lagi
            </a>
        </div>

        {{-- FILTER --}}
        <form method="GET" action="{{ route('customer.reservations') }}" class="row g-3 mb-3">
            <input type="hidden" name="tab" value="{{ $tab }}">

            <div class="col-lg-3 col-md-6">
                <label class="field-label">Cari kode booking atau resto</label>
                <div class="position-relative">
                    <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="Cari reservasi Anda…">
                    <button type="submit" class="btn border-0 position-absolute top-50 end-0 translate-middle-y" aria-label="Cari">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <label class="field-label">Periode kunjungan</label>
                <input type="month" class="form-control" name="periode" value="{{ request('periode') }}" onchange="this.form.submit()">
            </div>

            <div class="col-lg-3 col-md-6">
                <label class="field-label">Status reservasi</label>
                <select class="form-select" name="status" onchange="this.form.submit()">
                    <option value="">Semua status</option>
                    @foreach ($opsiStatus as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(request('status') === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-3 col-md-6">
                <label class="field-label">Status DP</label>
                <select class="form-select" name="dp" onchange="this.form.submit()">
                    <option value="">Semua status DP</option>
                    @foreach ($opsiDp as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(request('dp') === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        {{-- TAB --}}
        <div class="d-flex flex-wrap gap-2 mb-3">
            @foreach ($tabs as $key => $label)
                <a class="chip {{ $tab === $key ? 'active' : '' }}"
                   href="{{ route('customer.reservations', array_merge(request()->except(['page', 'tab']), ['tab' => $key])) }}">
                    {{ $label }} · {{ $hitung[$key] }}
                </a>
            @endforeach
        </div>

        {{-- TABEL --}}
        <div class="panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Reservasi Anda</h5>
                <span class="sub">{{ request('periode') ? \Carbon\Carbon::createFromFormat('!Y-m', request('periode'))->locale('id')->translatedFormat('F Y') : 'Semua periode' }}</span>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="thead-lav">
                        <tr>
                            <th>Booking / resto</th>
                            <th>Tanggal & jam</th>
                            <th>Status reservasi</th>
                            <th>Status DP</th>
                            <th>Total pesanan</th>
                            <th>Sisa bayar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reservasis as $r)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $r->kode_booking }}</div>
                                    <div class="sub">{{ $r->resto->nama }} · {{ $r->jumlah_orang }} orang</div>
                                </td>
                                <td>
                                    <div>{{ $r->tanggal->locale('id')->translatedFormat('d M Y') }}</div>
                                    <div class="sub">{{ $r->jam_rentang }}</div>
                                </td>
                                <td><span class="status status-{{ $r->status }}">{{ $r->status_label }}</span></td>
                                <td>
                                    <span class="status {{ $r->dp_kelas }}">{{ $r->dp_label }}</span>
                                    <div class="sub mt-1">{{ $r->dp_catatan }}</div>
                                </td>
                                <td>{{ $rupiah($r->total_harga) }}</td>
                                <td>{{ $rupiah($r->sisa_bayar) }}</td>
                                <td>
                                    <a href="{{ route('customer.reservations.show', $r) }}"
                                       class="btn btn-sm {{ $r->bisa_diedit ? 'btn-purple' : 'btn-outline-purple' }}">
                                        {{ $r->bisa_diedit ? 'Detail / ubah' : 'Detail' }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <h5>Tidak ada reservasi</h5>
                                    <p class="subtitle mb-3">Belum ada reservasi yang cocok dengan filter ini.</p>
                                    <a href="{{ route('customer.index') }}" class="btn btn-purple">Cari resto</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                <span class="sub">
                    Menampilkan {{ $reservasis->firstItem() ?? 0 }}–{{ $reservasis->lastItem() ?? 0 }} dari {{ $reservasis->total() }} reservasi · Nominal sisa bukan tagihan untuk reservasi yang batal.
                </span>
                <div class="d-flex gap-2 align-items-center">
                    @unless ($reservasis->onFirstPage())
                        <a class="btn btn-sm btn-outline-purple" href="{{ $reservasis->previousPageUrl() }}">Sebelumnya</a>
                    @endunless
                    <span class="btn btn-sm btn-outline-purple disabled">Halaman {{ $reservasis->currentPage() }}</span>
                    @if ($reservasis->hasMorePages())
                        <a class="btn btn-sm btn-outline-purple" href="{{ $reservasis->nextPageUrl() }}">Berikutnya</a>
                    @endif
                </div>
            </div>

            <p class="small mt-3 mb-0" style="color: var(--purple-dark);">
                Bukti yang masih diverifikasi belum dianggap pembayaran final. Sisa akan menyesuaikan jika DP ditolak.
            </p>
        </div>
    </div>

    @push('styles')
        <style>
            .status-dibatalkan { background: var(--red); color: #bd3448; }
            .sub { color: var(--muted); font-size: 12px; }
            .chip {
                border: 1px solid var(--line); background: #fff; color: var(--purple-dark);
                border-radius: 10px; padding: 8px 14px; font-size: 13px; font-weight: 600; text-decoration: none;
            }
            .chip:hover { background: var(--lav2); color: var(--purple-dark); }
            .chip.active { background: var(--purple); border-color: var(--purple); color: #fff; }
            .thead-lav th {
                background: var(--lav); color: var(--muted); font-size: 12px; font-weight: 600;
                border: 0; white-space: nowrap;
            }
        </style>
    @endpush
@endsection