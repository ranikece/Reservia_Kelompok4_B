@extends('layouts.app')

@section('title', 'Kelola resto dan menu')

@section('content')
    @php
        $bisaUbah = auth()->user()->isPemilik();
        $rupiah   = fn ($n) => 'Rp'.number_format($n, 0, ',', '.');

        // [nama field, label, tipe input, wajib?]
        $fieldResto = [
            ['nama',        'Nama restoran',      'text',   true],
            ['kota',        'Kota',               'text',   true],
            ['alamat',      'Alamat',             'text',   true],
            ['kapasitas',   'Kapasitas kursi',    'number', true],
            ['jam_buka',    'Jam buka',           'time',   true],
            ['jam_tutup',   'Jam tutup',          'time',   true],
            ['dp_persen',   'DP (%)',             'number', true],
            ['nama_bank',   'Nama bank',          'text',   false],
            ['no_rekening', 'Nomor rekening',     'text',   false],
            ['atas_nama',   'Atas nama rekening', 'text',   false],
        ];
    @endphp

    <div class="page">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1>Kelola resto &amp; menu</h1>
                <p class="subtitle mb-0">Tambah dan perbarui informasi restoran serta menu yang tersedia.</p>
            </div>
            <a class="btn btn-outline-purple" href="{{ route('owner.dashboard') }}">Kembali ke dashboard</a>
        </div>

        @unless ($bisaUbah)
            <div class="panel mb-4" style="background:#f5f0fb">
                <i class="bi bi-eye me-1" style="color:var(--purple)"></i>
                <strong style="color:var(--purple-dark)">Mode lihat saja.</strong>
                <span class="subtitle">Admin dapat melihat semua resto, tetapi data hanya bisa diubah oleh pemiliknya.</span>
            </div>
        @endunless

        @if ($bisaUbah)
            <div class="panel mb-4">
                <h5 class="mb-3">Tambah restoran</h5>
                <form method="POST" action="{{ route('owner.restos.store') }}" enctype="multipart/form-data" class="row g-3">
                    @csrf

                    @foreach ($fieldResto as [$name, $label, $type, $wajib])
                        <div class="col-md-6">
                            <label class="field-label">{{ $label }}{{ $wajib ? ' *' : '' }}</label>
                            <input class="form-control" name="{{ $name }}" type="{{ $type }}"
                                   value="{{ old($name, $name === 'dp_persen' ? 30 : '') }}"
                                   @if ($name === 'kapasitas') min="1" max="10000" @endif
                                   @if ($name === 'dp_persen') min="0" max="100" @endif
                                   @required($wajib)>
                        </div>
                    @endforeach

                    <div class="col-md-6">
                        <label class="field-label">Fasilitas</label>
                        <input class="form-control" name="fasilitas" value="{{ old('fasilitas') }}" placeholder="Wi-Fi, parkir, musala">
                    </div>
                    <div class="col-md-6">
                        <label class="field-label">Foto restoran</label>
                        <input class="form-control" type="file" name="foto" accept="image/*">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-purple">Simpan restoran</button>
                    </div>
                </form>
            </div>
        @endif

        @forelse ($restos as $resto)
            <div class="panel mb-4">

                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div class="d-flex gap-3">
                        @if ($resto->foto)
                            <img src="{{ asset('storage/'.$resto->foto) }}" alt="Foto {{ $resto->nama }}" class="menu-thumb">
                        @else
                            <div class="menu-thumb menu-thumb-ph"><i class="bi bi-shop"></i></div>
                        @endif
                        <div>
                            <h5 class="mb-1">{{ $resto->nama }}</h5>
                            <p class="subtitle mb-1">{{ $resto->alamat }}, {{ $resto->kota }} · {{ $resto->kapasitas }} kursi · DP {{ $resto->dp_persen }}%</p>
                            @unless ($bisaUbah)
                                <p class="subtitle small mb-0">Pemilik: {{ $resto->pemilik->name ?? '-' }}</p>
                            @endunless
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if ($resto->is_active)
                            <span class="status status-dikonfirmasi">Aktif</span>
                        @else
                            <span class="status status-menunggu">Libur · tidak muncul di pencarian</span>
                        @endif
                        <span class="status status-check_in">{{ $resto->menus_count }} menu</span>

                        @if ($bisaUbah)
                            <form method="POST" action="{{ route('owner.restos.toggle', $resto) }}" class="m-0"
                                  onsubmit="return confirm('{{ $resto->is_active ? 'Nonaktifkan resto ini sementara? Resto tidak akan muncul di pencarian pelanggan.' : 'Aktifkan kembali resto ini?' }}')">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-outline-purple">
                                    {{ $resto->is_active ? 'Nonaktifkan (libur)' : 'Aktifkan kembali' }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                @if ($bisaUbah)
                    <details class="my-3">
                        <summary style="color:var(--purple);cursor:pointer">Edit informasi restoran</summary>
                        <form class="row g-3 mt-1" method="POST" action="{{ route('owner.restos.update', $resto) }}" enctype="multipart/form-data">
                            @csrf @method('PUT')

                            @foreach ($fieldResto as [$name, $label, $type, $wajib])
                                @php
                                    $nilai = in_array($name, ['jam_buka', 'jam_tutup'], true)
                                        ? substr((string) $resto->$name, 0, 5)
                                        : $resto->$name;
                                @endphp
                                <div class="col-md-6">
                                    <label class="field-label">{{ $label }}{{ $wajib ? ' *' : '' }}</label>
                                    <input class="form-control" name="{{ $name }}" type="{{ $type }}" value="{{ $nilai }}"
                                           @if ($name === 'kapasitas') min="1" max="10000" @endif
                                           @if ($name === 'dp_persen') min="0" max="100" @endif
                                           @required($wajib)>
                                </div>
                            @endforeach

                            <div class="col-md-6">
                                <label class="field-label">Fasilitas</label>
                                <input class="form-control" name="fasilitas" value="{{ $resto->fasilitas }}">
                            </div>
                            <div class="col-md-6">
                                <label class="field-label">Ganti foto</label>
                                <input class="form-control" type="file" name="foto" accept="image/*">
                            </div>
                            <div class="col-12">
                                <button class="btn btn-purple">Simpan perubahan</button>
                            </div>
                        </form>
                    </details>
                @endif

                <hr>

                <h6 class="mb-3">Menu restoran</h6>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Menu</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Status</th>
                                @if ($bisaUbah) <th>Aksi</th> @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($resto->menus as $menu)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if ($menu->foto)
                                                <img src="{{ asset('storage/'.$menu->foto) }}" alt="{{ $menu->nama }}" class="menu-thumb menu-thumb-sm">
                                            @else
                                                <div class="menu-thumb menu-thumb-sm menu-thumb-ph"><i class="bi bi-egg-fried"></i></div>
                                            @endif
                                            <span>{{ $menu->nama }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $menu->kategori }}</td>
                                    <td>{{ $rupiah($menu->harga) }}</td>
                                    <td>
                                        <span class="status {{ $menu->tersedia ? 'status-tersedia' : 'status-penuh' }}">
                                            {{ $menu->tersedia ? 'Tersedia' : 'Habis' }}
                                        </span>
                                    </td>

                                    @if ($bisaUbah)
                                        <td>
                                            <details>
                                                <summary style="cursor:pointer;color:var(--purple)">Edit</summary>
                                                <form method="POST" action="{{ route('owner.menus.update', $menu) }}" enctype="multipart/form-data"
                                                      class="d-flex flex-wrap gap-2 align-items-center mt-2">
                                                    @csrf @method('PUT')
                                                    <input class="form-control" name="nama" value="{{ $menu->nama }}" required style="max-width:180px">
                                                    <input class="form-control" name="kategori" value="{{ $menu->kategori }}" list="daftar-kategori" required style="max-width:130px">
                                                    <input class="form-control" type="number" min="0" name="harga" value="{{ $menu->harga }}" required style="max-width:120px">
                                                    <input class="form-control" type="file" name="foto" accept="image/*" style="max-width:200px" aria-label="Ganti foto menu">
                                                    <input type="hidden" name="tersedia" value="0">
                                                    <label class="small">
                                                        <input type="checkbox" name="tersedia" value="1" @checked($menu->tersedia)> Tersedia
                                                    </label>
                                                    <button class="btn btn-sm btn-purple">Simpan</button>
                                                </form>
                                            </details>

                                            <form method="POST" action="{{ route('owner.menus.delete', $menu) }}" class="mt-1"
                                                  onsubmit="return confirm('Hapus menu ini?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="{{ $bisaUbah ? 5 : 4 }}" class="subtitle">Belum ada menu.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($bisaUbah)
                    @php $formIni = old('resto_id') === $resto->id; @endphp
                    <form method="POST" action="{{ route('owner.menus.store') }}" enctype="multipart/form-data" class="row g-2 align-items-end mt-2">
                        @csrf
                        <input type="hidden" name="resto_id" value="{{ $resto->id }}">

                        <div class="col-md-3">
                            <label class="field-label">Nama menu *</label>
                            <input class="form-control" name="nama" value="{{ $formIni ? old('nama') : '' }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="field-label">Kategori *</label>
                            <input class="form-control" name="kategori" list="daftar-kategori" placeholder="Makanan"
                                   value="{{ $formIni ? old('kategori') : '' }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="field-label">Harga *</label>
                            <input class="form-control" type="number" min="0" name="harga" value="{{ $formIni ? old('harga') : '' }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="field-label">Foto menu</label>
                            <input class="form-control" type="file" name="foto" accept="image/*">
                        </div>
                        <div class="col-md-2">
                            <input type="hidden" name="tersedia" value="0">
                            <label class="small d-block mb-2">
                                <input type="checkbox" name="tersedia" value="1" checked> Tersedia
                            </label>
                            <button class="btn btn-purple w-100">Tambah menu</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('owner.restos.delete', $resto) }}" class="mt-3"
                          onsubmit="return confirm('Hapus restoran beserta seluruh menu dan riwayat reservasinya? Tindakan ini tidak bisa dibatalkan.')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Hapus restoran</button>
                    </form>
                @endif
            </div>
        @empty
            <div class="panel text-center py-5">
                <h5>Belum ada restoran</h5>
                <p class="subtitle mb-0">
                    {{ $bisaUbah ? 'Tambahkan restoran melalui formulir di atas.' : 'Belum ada pemilik yang menambahkan restoran.' }}
                </p>
            </div>
        @endforelse

        <datalist id="daftar-kategori">
            <option value="Makanan">
            <option value="Minuman">
            <option value="Paket">
            <option value="Dessert">
        </datalist>
    </div>

    @push('styles')
        <style>
            .menu-thumb { width: 64px; height: 64px; border-radius: 12px; object-fit: cover; flex-shrink: 0; }
            .menu-thumb-sm { width: 40px; height: 40px; border-radius: 8px; }
            .menu-thumb-ph {
                display: flex; align-items: center; justify-content: center;
                background: var(--lav2); color: var(--purple); font-size: 22px;
            }
            .menu-thumb-sm.menu-thumb-ph { font-size: 17px; }
        </style>
    @endpush
@endsection