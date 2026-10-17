@extends('layouts.app')

@section('title', 'Detail reservasi')

@section('content')
    @php
        $rupiah = fn ($n) => 'Rp'.number_format($n, 0, ',', '.');
        $resto  = $reservasi->resto;
        $dibuat = $reservasi->created_at->copy()->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d F Y, H.i');
        $bisaUbah = auth()->user()->isPemilik();
        $masihAktif = in_array($reservasi->status, ['menunggu', 'dikonfirmasi'], true);
    @endphp

    <div class="page">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div>
                <h1 class="mb-1">Detail reservasi</h1>
                <p class="subtitle mb-0">{{ $reservasi->kode_booking }} · {{ $resto->nama }} · Dibuat {{ $dibuat }} WIB</p>
            </div>
            <a href="{{ route('owner.reservations') }}" class="btn btn-outline-purple">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke daftar
            </a>
        </div>

        <div class="row g-4">

            {{-- ===== KOLOM KIRI ===== --}}
            <div class="col-lg-8">

                {{-- Status --}}
                <div class="panel mb-3">
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <span class="status status-{{ $reservasi->status }}">Reservasi: {{ $reservasi->status_label }}</span>
                        <span class="status {{ $reservasi->dp_kelas }}">DP: {{ $reservasi->dp_label }}</span>
                        @if ($reservasi->status_pembayaran === 'lunas')
                            <span class="status status-dikonfirmasi">Pembayaran lunas</span>
                        @else
                            <span class="status status-menunggu">Belum lunas</span>
                        @endif
                    </div>

                    @if ($reservasi->alasan_penolakan)
                        <p class="small mt-2 mb-0" style="color:#bd3448;">
                            {{ $reservasi->batal_otomatis ? 'Dibatalkan sistem' : 'Alasan penolakan / catatan' }}:
                            {{ $reservasi->alasan_penolakan }}
                        </p>
                    @endif
                </div>

                {{-- Pelanggan --}}
                <div class="panel mb-3">
                    <h5 class="mb-3">Pelanggan</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="sub">Nama</div>
                            <strong>{{ $reservasi->user->name ?? 'Pelanggan' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <div class="sub">Nomor HP</div>
                            <strong>{{ $reservasi->user->no_hp ?: '-' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <div class="sub">Email</div>
                            <strong>{{ $reservasi->user->email ?? '-' }}</strong>
                        </div>
                    </div>
                </div>

                {{-- Jadwal --}}
                <div class="panel mb-3">
                    <h5 class="mb-3">Jadwal kunjungan</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="sub">Tanggal</div>
                            <strong>{{ $reservasi->tanggal->locale('id')->translatedFormat('l, d F Y') }}</strong>
                        </div>
                        <div class="col-md-4">
                            <div class="sub">Jam</div>
                            <strong>{{ $reservasi->jam_rentang }} WIB</strong>
                        </div>
                        <div class="col-md-4">
                            <div class="sub">Jumlah tamu</div>
                            <strong>{{ $reservasi->jumlah_orang }} orang</strong>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="sub">Catatan pelanggan</div>
                        <div>{{ $reservasi->catatan ?: 'Tidak ada catatan.' }}</div>
                    </div>
                </div>

                {{-- Pesanan --}}
                <div class="panel">
                    <h5 class="mb-3">Pesanan menu</h5>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="thead-lav">
                                <tr>
                                    <th>Menu</th>
                                    <th class="text-end">Harga</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($reservasi->items as $item)
                                    <tr>
                                        <td>
                                            {{ $item->menu->nama ?? 'Menu dihapus' }}
                                            <div class="sub">{{ $item->menu->kategori ?? '' }}</div>
                                        </td>
                                        <td class="text-end">{{ $rupiah($item->harga_satuan) }}</td>
                                        <td class="text-center">{{ $item->jumlah }}</td>
                                        <td class="text-end">{{ $rupiah($item->harga_satuan * $item->jumlah) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center subtitle py-3">Tidak ada item pesanan.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Total pesanan</td>
                                    <td class="text-end fw-bold">{{ $rupiah($reservasi->total_harga) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ===== KOLOM KANAN ===== --}}
            <div class="col-lg-4">

                {{-- Tindakan reservasi --}}
                <div class="panel mb-3">
                    <h5 class="mb-3">Tindakan</h5>

                    @if (! $bisaUbah)
                        <p class="subtitle small mb-0">Mode lihat saja. Hanya pemilik resto yang dapat mengubah status reservasi.</p>
                    @else
                        @php
                            $langkah = ['menunggu' => 'Menunggu', 'dikonfirmasi' => 'Konfirmasi', 'check_in' => 'Check-in', 'selesai' => 'Selesai'];
                            $posisi  = array_search($reservasi->status, array_keys($langkah), true);
                            $urlStatus = route('owner.reservasi.status', $reservasi);
                        @endphp

                        @if ($posisi !== false)
                            <div class="stepper mb-3">
                                @foreach ($langkah as $kunci => $label)
                                    <div class="step {{ $loop->index < $posisi ? 'done' : ($loop->index === $posisi ? 'now' : '') }}">
                                        <span class="dot"></span><span class="lbl">{{ $label }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if ($reservasi->status === 'menunggu')
                            <form method="POST" action="{{ $urlStatus }}" class="mb-2">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="dikonfirmasi">
                                <button class="btn btn-purple w-100"><i class="bi bi-check2-circle me-1"></i> Konfirmasi reservasi</button>
                            </form>
                            @if ($reservasi->status_dp !== 'diterima')
                                <p class="sub mb-2">DP belum terverifikasi. Anda tetap dapat mengonfirmasi reservasi ini.</p>
                            @endif
                        @endif

                        @if ($reservasi->status === 'dikonfirmasi')
                            <form method="POST" action="{{ $urlStatus }}" class="mb-2">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="check_in">
                                <button class="btn btn-purple w-100"><i class="bi bi-box-arrow-in-right me-1"></i> Tandai check-in</button>
                            </form>
                        @endif

                        @if ($reservasi->status === 'check_in')
                            <form method="POST" action="{{ $urlStatus }}" class="mb-2"
                                  onsubmit="return confirm('Tandai reservasi selesai? Kursi akan kembali tersedia untuk jadwal ini.')">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="selesai">
                                <button class="btn btn-purple w-100"><i class="bi bi-flag me-1"></i> Tandai selesai</button>
                            </form>
                        @endif

                        @if (in_array($reservasi->status, ['dikonfirmasi', 'check_in', 'selesai'], true) && $reservasi->status_pembayaran !== 'lunas')
                            <form method="POST" action="{{ $urlStatus }}" class="mb-2"
                                  onsubmit="return confirm('Tandai pembayaran lunas? Sisa {{ $rupiah($reservasi->sisa_bayar) }} sudah diterima.')">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="lunas">
                                <button class="btn btn-outline-purple w-100"><i class="bi bi-cash-coin me-1"></i> Tandai lunas</button>
                            </form>
                        @endif

                        @if ($masihAktif)
                            <details class="mt-2" @if ($errors->has('alasan_penolakan')) open @endif>
                                <summary style="cursor:pointer;color:#bd3448">Tolak reservasi</summary>
                                <form method="POST" action="{{ $urlStatus }}" class="mt-2"
                                      onsubmit="return confirm('Tolak reservasi ini? Jika DP sudah dibayar, pengembalian mengikuti kebijakan resto.')">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="tolak">
                                    <label class="field-label">Alasan penolakan *</label>
                                    <textarea class="form-control mb-2" name="alasan_penolakan" rows="2" maxlength="500" required
                                              placeholder="Contoh: resto penuh pada jam tersebut">{{ old('alasan_penolakan') }}</textarea>
                                    <button class="btn w-100" style="background:var(--red);color:#bd3448;border:1px solid #f1cbd2;">Ya, tolak reservasi</button>
                                </form>
                            </details>
                        @endif

                        @if (in_array($reservasi->status, ['ditolak', 'dibatalkan'], true))
                            <p class="subtitle small mb-0">Reservasi ini sudah {{ $reservasi->status === 'ditolak' ? 'ditolak' : 'dibatalkan' }}, tidak ada tindakan lagi.</p>
                        @elseif ($reservasi->status === 'selesai' && $reservasi->status_pembayaran === 'lunas')
                            <p class="subtitle small mb-0">Reservasi selesai dan pembayaran sudah lunas.</p>
                        @endif
                    @endif
                </div>

                {{-- Ringkasan pembayaran --}}
                <div class="panel mb-3">
                    <h5 class="mb-3">Ringkasan pembayaran</h5>
                    <div class="d-flex justify-content-between py-1">
                        <span class="subtitle">Total pesanan</span>
                        <strong>{{ $rupiah($reservasi->total_harga) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="subtitle">DP {{ $resto->dp_persen }}%</span>
                        <span>{{ $rupiah($reservasi->dp_nominal) }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="subtitle">Sisa bayar di resto</span>
                        <strong>{{ $rupiah($reservasi->sisa_bayar) }}</strong>
                    </div>
                    <hr>
                    <div class="sub">Rekening tujuan DP</div>
                    <div class="small">
                        {{ $resto->nama_bank ?: 'Belum diisi' }}
                        @if ($resto->no_rekening) · <strong>{{ $resto->no_rekening }}</strong> @endif
                        @if ($resto->atas_nama) · a.n. {{ $resto->atas_nama }} @endif
                    </div>
                </div>

                {{-- Bukti DP --}}
                <div class="panel mb-3">
                    <h5 class="mb-2">Bukti transfer DP</h5>
                    <span class="status {{ $reservasi->dp_kelas }}">{{ $reservasi->dp_label }}</span>
                    <div class="sub mt-1 mb-3">{{ $reservasi->dp_catatan }}</div>

                    @if ($reservasi->bukti_dp)
                        <a href="{{ asset('storage/'.$reservasi->bukti_dp) }}" target="_blank" rel="noopener">
                            <img src="{{ asset('storage/'.$reservasi->bukti_dp) }}" alt="Bukti transfer DP" class="bukti-img">
                        </a>
                        <p class="small mt-2 mb-0"><a href="{{ asset('storage/'.$reservasi->bukti_dp) }}" target="_blank" rel="noopener">Buka gambar penuh</a></p>
                    @else
                        <div class="bukti-kosong">
                            <i class="bi bi-image"></i>
                            <div class="small">Pelanggan belum mengunggah bukti.</div>
                        </div>
                    @endif

                    @if ($bisaUbah && $masihAktif && $reservasi->bukti_dp && $reservasi->status_dp === 'menunggu_verifikasi')
                        <hr>
                        <p class="small fw-bold mb-2">Verifikasi DP {{ $rupiah($reservasi->dp_nominal) }}</p>
                        <p class="sub">Pastikan nominal dan nama pengirim pada bukti sudah sesuai sebelum menerima.</p>

                        <form method="POST" action="{{ route('owner.reservasi.dp', $reservasi) }}" class="mb-2">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status_dp" value="diterima">
                            <button class="btn btn-purple w-100"><i class="bi bi-check2 me-1"></i> Terima DP</button>
                        </form>

                        <details @if ($errors->has('catatan_dp')) open @endif>
                            <summary style="cursor:pointer;color:#bd3448">Tolak DP</summary>
                            <form method="POST" action="{{ route('owner.reservasi.dp', $reservasi) }}" class="mt-2">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status_dp" value="ditolak">
                                <label class="field-label">Alasan penolakan DP *</label>
                                <textarea class="form-control mb-2" name="catatan_dp" rows="2" maxlength="500" required
                                          placeholder="Contoh: nominal transfer tidak sesuai">{{ old('catatan_dp') }}</textarea>
                                <button class="btn w-100" style="background:var(--red);color:#bd3448;border:1px solid #f1cbd2;">Ya, tolak DP</button>
                            </form>
                        </details>
                    @endif
                </div>

            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .status-dibatalkan { background: var(--red); color: #bd3448; }
            .sub { color: var(--muted); font-size: 12px; }
            .thead-lav th {
                background: var(--lav); color: var(--muted); font-size: 12px; font-weight: 600;
                border: 0; white-space: nowrap;
            }
            .bukti-img {
                width: 100%; max-height: 360px; object-fit: contain;
                border: 1px solid var(--line); border-radius: 12px; background: var(--lav);
            }
            .bukti-kosong {
                text-align: center; color: var(--muted); padding: 28px 12px;
                border: 1px dashed var(--line); border-radius: 12px; background: var(--lav);
            }
            .bukti-kosong i { font-size: 26px; }
            .stepper { display: flex; justify-content: space-between; gap: 4px; }
            .step { flex: 1; text-align: center; font-size: 11px; color: var(--muted); }
            .step .dot { display: block; width: 14px; height: 14px; margin: 0 auto 4px; border-radius: 50%; background: var(--line); }
            .step.done .dot { background: #16805a; }
            .step.now .dot { background: var(--purple); box-shadow: 0 0 0 4px var(--lav2); }
            .step.now .lbl { color: var(--purple-dark); font-weight: 700; }
        </style>
    @endpush
@endsection