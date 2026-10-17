@extends('layouts.app')

@section('title', 'Reservasi masuk')

@section('content')
    @php
        $rupiah = fn ($n) => 'Rp'.number_format($n, 0, ',', '.');

        $opsiStatus = [
            'menunggu'     => 'Menunggu konfirmasi',
            'dikonfirmasi' => 'Dikonfirmasi',
            'check_in'     => 'Sudah check-in',
            'selesai'      => 'Selesai',
            'ditolak'      => 'Ditolak',
            'dibatalkan'   => 'Dibatalkan',
        ];
        $opsiDp = [
            'belum_bayar'         => 'Belum bayar',
            'menunggu_verifikasi' => 'Menunggu verifikasi',
            'diterima'            => 'Terverifikasi',
            'ditolak'             => 'Ditolak',
        ];

        $statusAktif = request('status', '');
        $totalSemua  = $hitung->sum();
        $adaFilter   = request()->hasAny(['q', 'tanggal', 'status', 'dp', 'resto']);
    @endphp

    <div class="page">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div>
                <h1 class="mb-1">Reservasi masuk</h1>
                <p class="subtitle mb-0">Semua reservasi dari pelanggan, lengkap dengan status pemesanan dan pembayaran DP.</p>
            </div>
            <a href="{{ route('owner.dashboard') }}" class="btn btn-outline-purple">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke dashboard
            </a>
        </div>

        {{-- Pengingat bukti DP --}}
        @if ($perluVerifikasi > 0)
            <div class="panel mb-3" style="background:var(--amber);border-color:#f3dcb0">
                <i class="bi bi-hourglass-split me-1" style="color:#a65c00"></i>
                <strong style="color:#a65c00">{{ $perluVerifikasi }} bukti DP menunggu verifikasi.</strong>
                <a href="{{ route('owner.reservations', ['dp' => 'menunggu_verifikasi']) }}" class="ms-1">Tampilkan</a>
            </div>
        @endif

        {{-- FILTER --}}
        <form method="GET" action="{{ route('owner.reservations') }}" class="row g-3 mb-3">
            @if ($statusAktif !== '')
                <input type="hidden" name="status" value="{{ $statusAktif }}">
            @endif

            <div class="col-lg-3 col-md-6">
                <label class="field-label">Cari nama pelanggan / kode booking</label>
                <div class="position-relative">
                    <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="Contoh: Andi atau AB12">
                    <button type="submit" class="btn border-0 position-absolute top-50 end-0 translate-middle-y" aria-label="Cari">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <label class="field-label">Tanggal kunjungan</label>
                <input type="date" class="form-control" name="tanggal" value="{{ request('tanggal') }}" onchange="this.form.submit()">
            </div>

            <div class="col-lg-{{ $restos->count() > 1 ? 3 : 6 }} col-md-6">
                <label class="field-label">Status DP</label>
                <select class="form-select" name="dp" onchange="this.form.submit()">
                    <option value="">Semua status DP</option>
                    @foreach ($opsiDp as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(request('dp') === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            @if ($restos->count() > 1)
                <div class="col-lg-3 col-md-6">
                    <label class="field-label">Resto</label>
                    <select class="form-select" name="resto" onchange="this.form.submit()">
                        <option value="">Semua resto</option>
                        @foreach ($restos as $r)
                            <option value="{{ $r->id }}" @selected(request('resto') === $r->id)>{{ $r->nama }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                <a class="chip" href="{{ route('owner.reservations', array_merge(request()->except(['page', 'tanggal']), ['tanggal' => now()->toDateString()])) }}">Hari ini</a>
                @if ($adaFilter)
                    <a class="small text-decoration-none" style="color:var(--purple)" href="{{ route('owner.reservations') }}">Reset semua filter</a>
                @endif
            </div>
        </form>

        {{-- CHIP STATUS --}}
        <div class="d-flex flex-wrap gap-2 mb-3">
            <a class="chip {{ $statusAktif === '' ? 'active' : '' }}"
               href="{{ route('owner.reservations', request()->except(['page', 'status'])) }}">
                Semua · {{ $totalSemua }}
            </a>
            @foreach ($opsiStatus as $key => $label)
                <a class="chip {{ $statusAktif === $key ? 'active' : '' }}"
                   href="{{ route('owner.reservations', array_merge(request()->except(['page', 'status']), ['status' => $key])) }}">
                    {{ $label }} · {{ $hitung[$key] ?? 0 }}
                </a>
            @endforeach
        </div>

        {{-- TABEL --}}
        <div class="panel">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="thead-lav">
                        <tr>
                            <th>Booking / pelanggan</th>
                            @if ($restos->count() > 1) <th>Resto</th> @endif
                            <th>Tanggal &amp; jam</th>
                            <th>Tamu</th>
                            <th>Total / DP</th>
                            <th>Status</th>
                            <th>Status DP</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reservasis as $r)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $r->kode_booking }}</div>
                                    <div class="sub">{{ $r->user->name ?? 'Pelanggan' }}</div>
                                </td>
                                @if ($restos->count() > 1)
                                    <td>{{ $r->resto->nama ?? '-' }}</td>
                                @endif
                                <td>
                                    <div>{{ $r->tanggal->locale('id')->translatedFormat('d M Y') }}</div>
                                    <div class="sub">{{ $r->jam_rentang }}</div>
                                </td>
                                <td>{{ $r->jumlah_orang }} orang</td>
                                <td>
                                    {{ $rupiah($r->total_harga) }}
                                    <div class="sub">DP {{ $rupiah($r->dp_nominal) }}</div>
                                </td>
                                <td>
                                    <span class="status status-{{ $r->status }}">{{ $r->status_label }}</span>
                                    @if ($r->status_pembayaran === 'lunas')
                                        <div class="sub mt-1">Lunas</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="status {{ $r->dp_kelas }}">{{ $r->dp_label }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('owner.reservations.show', $r) }}" class="btn btn-sm btn-outline-purple">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $restos->count() > 1 ? 8 : 7 }}" class="text-center py-5">
                                    <h5>Tidak ada reservasi</h5>
                                    <p class="subtitle mb-0">
                                        {{ $adaFilter ? 'Belum ada reservasi yang cocok dengan filter ini.' : 'Belum ada reservasi masuk.' }}
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                <span class="sub">
                    Menampilkan {{ $reservasis->firstItem() ?? 0 }}–{{ $reservasis->lastItem() ?? 0 }} dari {{ $reservasis->total() }} reservasi
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