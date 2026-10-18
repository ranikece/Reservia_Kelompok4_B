@extends('layouts.app')

@section('title', 'Dashboard pemilik')

@section('content')
    @php
        $rupiah = fn ($n) => 'Rp'.number_format($n, 0, ',', '.');
        $banyakResto = $restos->count() > 1;
    @endphp

    <div class="page">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div>
                <h1 class="mb-1">Ruang pemilik</h1>
                <p class="subtitle mb-0">Ringkasan resto dan reservasi yang perlu ditindaklanjuti.</p>
            </div>
            <div class="text-lg-end">
                <span class="status" style="background:var(--lav2);color:var(--purple-dark)">
                    {{ $hariIni->copy()->locale('id')->translatedFormat('l, d F Y') }}
                </span>
                <div class="sub mt-2">
                    <span id="status-refresh">Diperbarui otomatis tiap 30 detik</span>
                    · <a href="#" id="btn-refresh" class="text-decoration-none" style="color:var(--purple)">Muat ulang</a>
                </div>
            </div>
        </div>

        {{-- ===== BAGIAN YANG DIPERBARUI OTOMATIS ===== --}}
        <div id="live">

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="panel">
                        <p class="subtitle mb-1">Jumlah resto</p>
                        <h2>{{ $restos->count() }}</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="panel">
                        <p class="subtitle mb-1">Jumlah menu</p>
                        <h2>{{ $restos->sum('menus_count') }}</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="panel">
                        <p class="subtitle mb-1">Reservasi menunggu</p>
                        <h2 class="d-inline">{{ $menunggu }}</h2>
                        @if ($menunggu > 0)
                            <a class="small ms-2" href="{{ route('owner.reservations', ['status' => 'menunggu']) }}">Lihat</a>
                        @endif
                    </div>
                </div>
            </div>

            @if ($perluVerifikasi > 0)
                <div class="panel mb-3" style="background:var(--amber);border-color:#f3dcb0">
                    <i class="bi bi-hourglass-split me-1" style="color:#a65c00"></i>
                    <strong style="color:#a65c00">{{ $perluVerifikasi }} bukti DP menunggu verifikasi.</strong>
                    <a href="{{ route('owner.reservations', ['dp' => 'menunggu_verifikasi']) }}" class="ms-1">Tampilkan</a>
                </div>
            @endif

            <div class="panel mb-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h5 class="mb-0">Reservasi hari ini · {{ $reservasisHariIni->count() }}</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('owner.reservations', ['tanggal' => $hariIni->toDateString()]) }}" class="btn btn-sm btn-outline-purple">Semua status</a>
                        <a href="{{ route('owner.reservations') }}" class="btn btn-sm btn-purple">Semua reservasi</a>
                        @if (auth()->user()->isPemilik())
                            <a href="{{ route('owner.restos') }}" class="btn btn-sm btn-outline-purple"><i class="bi bi-shop me-1"></i>Kelola resto &amp; menu</a>
                        @endif
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="thead-lav">
                            <tr>
                                <th>Jam</th>
                                <th>Pelanggan</th>
                                @if ($banyakResto) <th>Resto</th> @endif
                                <th>Tamu</th>
                                <th>Pesanan</th>
                                <th>Total / DP</th>
                                <th>Status</th>
                                <th>Status DP</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reservasisHariIni as $r)
                                <tr>
                                    <td class="fw-bold">{{ $r->jam_rentang }}</td>
                                    <td>
                                        {{ $r->user->name ?? 'Pelanggan' }}
                                        <div class="sub">{{ $r->kode_booking }}</div>
                                    </td>
                                    @if ($banyakResto)
                                        <td>{{ $r->resto->nama ?? '-' }}</td>
                                    @endif
                                    <td>{{ $r->jumlah_orang }} orang</td>
                                    <td class="small">
                                        {{ \Illuminate\Support\Str::limit($r->items->map(fn ($i) => $i->jumlah.'× '.($i->menu->nama ?? '-'))->implode(', '), 60) }}
                                    </td>
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
                                    <td><span class="status {{ $r->dp_kelas }}">{{ $r->dp_label }}</span></td>
                                    <td>
                                        <a href="{{ route('owner.reservations.show', $r) }}" class="btn btn-sm btn-outline-purple">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $banyakResto ? 9 : 8 }}" class="text-center py-5">
                                        <h5>Belum ada reservasi hari ini</h5>
                                        <p class="subtitle mb-0">Reservasi yang ditolak atau dibatalkan tidak ditampilkan di sini.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ===== GRAFIK ===== --}}
        <div class="panel">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h5 class="mb-0">Reservasi per hari</h5>
                <span class="sub">6 hari lalu sampai 7 hari ke depan · berdasarkan tanggal kunjungan · tidak termasuk ditolak/dibatalkan</span>
            </div>
            <div class="chart-box">
                <canvas id="grafik" aria-label="Grafik jumlah reservasi per hari" role="img"></canvas>
            </div>
            <p id="grafik-error" class="small mb-0 mt-2" style="color:#bd3448;display:none">Grafik gagal dimuat.</p>
        </div>
    </div>

    @push('styles')
        <style>
            .sub { color: var(--muted); font-size: 12px; }
            .thead-lav th {
                background: var(--lav); color: var(--muted); font-size: 12px; font-weight: 600;
                border: 0; white-space: nowrap;
            }
            .chart-box { position: relative; height: 260px; }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
        <script>
            const INTERVAL_MS = 30000; // auto-refresh tiap 30 detik
            const URL_GRAFIK = @json(route('owner.chart'));
            const elStatus = document.getElementById('status-refresh');
            let grafik = null;
            let sedangMuat = false;
            let timer = null;

            function jamSekarang() {
                return new Date().toLocaleTimeString('id-ID');
            }

            async function muatGrafik() {
                const res = await fetch(URL_GRAFIK, {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store',
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('Grafik gagal dimuat');
                const d = await res.json();

                // Bar hari ini diberi warna lebih tegas
                const warna = d.data.map((_, i) => i === d.hariIni ? '#7c61b7' : '#cfc2e8');

                if (!grafik) {
                    grafik = new Chart(document.getElementById('grafik'), {
                        type: 'bar',
                        data: {
                            labels: d.labels,
                            datasets: [{ label: 'Reservasi', data: d.data, backgroundColor: warna, borderRadius: 6 }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                        },
                    });
                } else {
                    grafik.data.labels = d.labels;
                    grafik.data.datasets[0].data = d.data;
                    grafik.data.datasets[0].backgroundColor = warna;
                    grafik.update();
                }
                document.getElementById('grafik-error').style.display = 'none';
            }

            // Ambil ulang halaman ini, lalu tukar isi #live saja (tanpa reload penuh)
            async function muatDashboard() {
                const res = await fetch(location.href, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    cache: 'no-store',
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('Halaman gagal dimuat');

                const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
                const baru = doc.getElementById('live');

                if (!baru) { // kemungkinan sesi habis dan diarahkan ke halaman login
                    clearInterval(timer);
                    elStatus.innerHTML = 'Sesi berakhir. <a href="' + location.href + '">Muat ulang halaman</a>';
                    return false;
                }
                document.getElementById('live').innerHTML = baru.innerHTML;
                return true;
            }

            async function segarkan() {
                if (sedangMuat) return;
                sedangMuat = true;
                try {
                    const [ok] = await Promise.all([
                        muatDashboard(),
                        muatGrafik().catch(() => { document.getElementById('grafik-error').style.display = 'block'; }),
                    ]);
                    if (ok) elStatus.textContent = 'Diperbarui ' + jamSekarang() + ' · otomatis tiap 30 detik';
                } catch (e) {
                    elStatus.textContent = 'Gagal memperbarui, mencoba lagi nanti';
                } finally {
                    sedangMuat = false;
                }
            }

            document.getElementById('btn-refresh').addEventListener('click', e => { e.preventDefault(); segarkan(); });

            // Jangan polling saat tab tidak terlihat; segarkan begitu tab dibuka lagi
            document.addEventListener('visibilitychange', () => { if (!document.hidden) segarkan(); });
            timer = setInterval(() => { if (!document.hidden) segarkan(); }, INTERVAL_MS);

            // Grafik pertama kali
            muatGrafik().catch(() => { document.getElementById('grafik-error').style.display = 'block'; });
        </script>
    @endpush
@endsection
