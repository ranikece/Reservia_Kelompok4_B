@extends('layouts.app') @section('title','Profil saya')

@push('styles')
<style>
    .btn-save {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 0;
        border-radius: 999px;
        padding: 12px 28px;
        font-weight: 700;
        font-size: 14px;
        color: #fff;
        background: linear-gradient(135deg, #8f74cc 0%, #7c61b7 50%, #60469b 100%);
        box-shadow: 0 8px 18px rgba(124, 97, 183, .32);
        transition: transform .2s, box-shadow .2s, filter .2s;
    }
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(124, 97, 183, .40);
        filter: brightness(1.05);
    }
    .btn-save:active {
        transform: translateY(0);
        box-shadow: 0 5px 12px rgba(124, 97, 183, .30);
    }
    .btn-save:focus-visible {
        outline: 3px solid #7c61b744;
        outline-offset: 3px;
    }
    @media (max-width: 768px) {
        .btn-save { width: 100%; justify-content: center; }
    }
</style>
@endpush

@section('content')
<div class="page">
    <div class="eyebrow"></div>
    <h1>Profil saya</h1>
    <p class="subtitle mb-4">Informasi yang tepat membantu resto menyambut Anda dengan lebih baik.</p>

    <div class="row g-4">

        {{-- Kolom kiri --}}
        <div class="col-lg-3">
            <div class="panel" style="background:#f4effa">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white mb-3" style="width:72px;height:72px;background:var(--purple);font-size:27px">{{ strtoupper(substr($user->name,0,1)) }}</div>
                <h5>{{ $user->name }}</h5>
                <p class="subtitle">{{ $user->email }}</p>
                <span class="status" style="background:var(--lav2);color:var(--purple-dark)">Akun pelanggan</span>
                <hr>
                <p style="color:var(--purple)"><i class="bi bi-person me-2"></i>Informasi pribadi</p>
                <a class="btn btn-outline-purple w-100" href="{{ route('customer.reservations') }}">Reservasi saya</a>
            </div>
            <p class="subtitle small mt-3">Bergabung sejak {{ $user->created_at->translatedFormat('F Y') }}.</p>
        </div>

        {{-- Kolom tengah: form --}}
        <div class="col-lg-6">
            <form method="POST" action="{{ route('customer.profile.update') }}" class="panel">
                @csrf @method('PUT')
                <h5>Edit informasi pribadi</h5>

                <div class="mt-4 mb-3">
                    <label class="field-label">Nama lengkap *</label>
                    <input class="form-control" name="name" value="{{ old('name',$user->name) }}" required>
                </div>
                <div class="mb-3">
                    <label class="field-label">Nomor HP *</label>
                    <input class="form-control" name="no_hp" value="{{ old('no_hp',$user->no_hp) }}" required>
                </div>
                <div class="mb-4">
                    <label class="field-label">Email akun</label>
                    <input class="form-control" value="{{ $user->email }}" disabled>
                    <small class="subtitle">Email tidak dapat diubah melalui form ini.</small>
                </div>

                <hr>

                <h5>Ubah password</h5>
                <p class="subtitle">Kosongkan bagian ini jika tidak ingin mengganti password.</p>
                <div class="mb-3">
                    <label class="field-label">Password baru</label>
                    <input class="form-control" type="password" name="password" minlength="8">
                </div>
                <div class="mb-4">
                    <label class="field-label">Konfirmasi password baru</label>
                    <input class="form-control" type="password" name="password_confirmation">
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn-save"><i class="bi bi-check2-circle"></i>Simpan perubahan</button>
                </div>
            </form>
        </div>

        {{-- Kolom kanan --}}
        <div class="col-lg-3">
            <div class="panel mb-3">
                <i class="bi bi-shield-check fs-3" style="color:var(--purple)"></i>
                <h5 class="mt-3">Data untuk kenyamanan.</h5>
                <p class="subtitle mb-0">Nama dan nomor HP digunakan untuk kebutuhan reservasi Anda.</p>
            </div>

            @if($upcoming)
            <div class="panel">
                <img class="restaurant-img mb-3" style="height:125px" src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=600&q=80" alt="">
                <h6>Reservasi terbaru</h6>
                <strong>{{ $upcoming->resto->nama }}</strong>
                <p class="subtitle small mt-2">{{ $upcoming->tanggal->format('d M Y') }} · {{ $upcoming->jam }} · {{ $upcoming->jumlah_orang }} orang</p>
                <span class="status status-{{ $upcoming->status }}">{{ str_replace('_',' ',$upcoming->status) }}</span>
                <a class="btn btn-outline-purple w-100 mt-3" href="{{ route('customer.reservations') }}">Lihat reservasi</a>
            </div>
            @endif
        </div>

    </div>
</div>
@endsection