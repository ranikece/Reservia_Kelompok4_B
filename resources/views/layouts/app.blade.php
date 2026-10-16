
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Reservia') | Reservia</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --purple: #7c61b7;
            --purple-dark: #60469b;
            --lav: #f7f4fb;
            --lav2: #eee7f8;
            --ink: #2d263b;
            --muted: #81788f;
            --line: #e6dff0;
            --green: #e8f6ef;
            --red: #fdecef;
            --amber: #fff3df;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: var(--lav);
            color: var(--ink);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            min-height: 100vh;
        }

        h1, h2, h3, h4, h5, .brand {
            font-family: 'Manrope', sans-serif;
            font-weight: 800;
            letter-spacing: -.4px;
        }

        .site-nav {
            min-height: 80px;
            background: #fff;
            border-bottom: 1px solid var(--line);
        }

        .site-nav .container {
            max-width: 1312px;
        }

        .brand {
            color: var(--purple-dark) !important;
            font-size: 25px;
            text-decoration: none;
            margin-right: 0;
        }

        .brand-mark {
            display: none !important;
        }

        .nav-link {
            color: var(--muted);
            padding: 12px 18px !important;
        }

        .nav-link.active,
        .nav-link:hover {
            color: var(--purple);
            font-weight: 700;
        }

        .user-dot {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--lav2);
            color: var(--purple);
            font-weight: 700;
        }

        .page {
            max-width: 1312px;
            margin: auto;
            padding: 28px 0 24px;
        }

        .eyebrow {
            display: none !important;
        }

        .subtitle {
            color: var(--muted);
        }

        .panel,
        .card-lav {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 17px;
            box-shadow: 0 7px 20px rgba(48, 30, 77, .025);
        }

        .panel {
            padding: 18px;
        }

        .btn-purple {
            background: var(--purple);
            border: 1px solid var(--purple);
            color: #fff;
            border-radius: 10px;
            padding: 10px 17px;
        }

        .btn-purple:hover {
            background: var(--purple-dark);
            border-color: var(--purple-dark);
            color: #fff;
        }

        .btn-outline-purple {
            border: 1px solid var(--line);
            color: var(--purple-dark);
            background: #fff;
            border-radius: 10px;
            padding: 10px 17px;
            transition: .2s;
        }

        .btn-outline-purple:hover {
            background: var(--lav2);
            border-color: var(--purple);
            color: var(--purple-dark);
        }

        .btn-logout {
            border: 1px solid var(--line);
            background: #fff;
            color: var(--purple-dark);
            border-radius: 10px;
            padding: 9px 14px;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            transition: .2s;
        }

        .btn-logout:hover {
            background: var(--red);
            border-color: #f1cbd2;
            color: #bd3448;
        }

        .form-control,
        .form-select {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 12px;
            color: var(--ink);
            font-size: 13px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #b5a2d6;
            box-shadow: 0 0 0 .2rem #7c61b71c;
        }

        .field-label {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 7px;
        }

        .restaurant-img {
            width: 100%;
            height: 185px;
            object-fit: cover;
            border-radius: 12px;
        }

        .status {
            display: inline-block;
            border-radius: 30px;
            padding: 6px 11px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-tersedia,
        .status-dikonfirmasi,
        .status-selesai {
            background: var(--green);
            color: #16805a;
        }

        .status-hampir_penuh,
        .status-menunggu {
            background: var(--amber);
            color: #a65c00;
        }

        .status-penuh,
        .status-ditolak {
            background: var(--red);
            color: #bd3448;
        }

        .status-check_in {
            background: var(--lav2);
            color: var(--purple-dark);
        }

        .site-footer {
            display: none !important;
        }

        .alert {
            border-radius: 12px;
        }

        .table > :not(caption) > * > * {
            padding: 12px 10px;
            border-bottom-color: var(--line);
        }

        @media (max-width: 768px) {
            .site-nav {
                height: auto;
            }

            .site-nav .navbar-collapse {
                padding: 12px 0;
            }

            .site-nav .navbar-nav {
                align-items: stretch !important;
            }

            .site-nav .nav-link {
                padding: 9px 0 !important;
            }

            .site-nav .user-dot {
                margin: 8px 0;
            }

            .site-nav .btn-logout {
                margin-top: 8px;
            }

            .page {
                padding: 22px 14px;
            }

            .restaurant-img {
                height: 210px;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

    @php($user = auth()->user())

    {{-- NAVBAR --}}
    <nav class="navbar navbar-expand-lg site-nav">
        <div class="container">

            {{-- Logo tanpa kotak ikon --}}
            <a class="navbar-brand brand"
               href="{{ auth()->check() && auth()->user()->role === 'pemilik'
                    ? route('owner.dashboard')
                    : (auth()->check() && auth()->user()->role === 'pelanggan'
                        ? route('customer.index')
                        : route('login')) }}">
                Reservia
            </a>

            <button class="navbar-toggler"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#nav"
                    aria-controls="nav"
                    aria-expanded="false"
                    aria-label="Buka navigasi">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="nav">
                <ul class="navbar-nav ms-auto align-items-lg-center">

                    @auth

                        @if ($user->role === 'pelanggan')

                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('customer.index', 'customer.show', 'customer.reserve*') ? 'active' : '' }}"
                                   href="{{ route('customer.index') }}">
                                    Cari resto
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('customer.reservations') ? 'active' : '' }}"
                                   href="{{ route('customer.reservations') }}">
                                    Reservasi saya
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('customer.profile*') ? 'active' : '' }}"
                                   href="{{ route('customer.profile') }}">
                                    Profil
                                </a>
                            </li>

                        @elseif ($user->role === 'pemilik')

                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('owner.dashboard') ? 'active' : '' }}"
                                   href="{{ route('owner.dashboard') }}">
                                    Dashboard
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('owner.restos*') ? 'active' : '' }}"
                                   href="{{ route('owner.restos') }}">
                                    Resto & menu
                                </a>
                            </li>

                        @else

                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('/admin/dashboard') }}">
                                    Dashboard
                                </a>
                            </li>

                        @endif

                        {{-- Avatar --}}
                        <li class="nav-item ms-lg-3">
                            <span class="user-dot">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </span>
                        </li>

                        {{-- Nama akun --}}
                        <li class="nav-item">
                            <span class="nav-link text-dark">
                                {{ $user->name }}
                            </span>
                        </li>

                        {{-- Tombol logout --}}
                        <li class="nav-item ms-lg-2">
                            <form action="{{ route('logout') }}"
                                  method="POST"
                                  class="m-0">
                                @csrf

                                <button type="submit" class="btn-logout">
                                    <i class="bi bi-box-arrow-right me-1"></i>
                                    Keluar
                                </button>
                            </form>
                        </li>

                    @endauth

                    @guest

                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">
                                Masuk
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="btn btn-purple" href="{{ route('register') }}">
                                Daftar
                            </a>
                        </li>

                    @endguest

                </ul>
            </div>
        </div>
    </nav>

    {{-- KONTEN --}}
    <main class="flex-grow-1">

        @if (session('success') || session('error') || $errors->any())

            <div class="container mt-3">

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button"
                                class="btn-close"
                                data-bs-dismiss="alert"
                                aria-label="Tutup"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button"
                                class="btn-close"
                                data-bs-dismiss="alert"
                                aria-label="Tutup"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Periksa kembali:</strong>

                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>

                        <button type="button"
                                class="btn-close"
                                data-bs-dismiss="alert"
                                aria-label="Tutup"></button>
                    </div>
                @endif

            </div>

        @endif

        @yield('content')

    </main>

    {{-- Footer disembunyikan --}}

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')

</body>
</html>
