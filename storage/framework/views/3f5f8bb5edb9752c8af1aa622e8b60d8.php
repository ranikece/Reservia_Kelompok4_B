 <?php $__env->startSection('title','Masuk pelanggan'); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .auth-wrap { padding: 20px 32px; }
    .auth-form { max-width: 460px; padding: 0 12px; }
    .auth-form .mb-3 { margin-bottom: .8rem !important; }
    .auth-hero { min-height: 320px; }

    @media (min-width: 992px) {
        .auth-row { min-height: calc(100vh - 130px); }
        .auth-hero { min-height: 0; }
    }
    @media (max-width: 768px) {
        .auth-wrap { padding: 16px; }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid auth-wrap">
    <div class="row g-4 align-items-stretch auth-row">

        <div class="col-lg-6">
            <div class="h-100 rounded-4 position-relative overflow-hidden d-flex align-items-end p-5 text-white auth-hero" style="background:linear-gradient(0deg,rgba(31,20,39,.88),transparent 70%),url('<?php echo e(asset('images/auth-lamp.jpg')); ?>') center/cover">
                <a class="brand bg-white rounded-4 p-3 text-decoration-none position-absolute top-0 start-0 m-4" href="<?php echo e(route('login')); ?>"><span class="brand-mark"><i class="bi bi-fork-knife"></i></span>Reservia</a>
                <div>
                    <h1 class="display-6 mb-3">Meja terbaik untuk momen yang berarti.</h1>
                    <p>Temukan rasa favorit, pilih menu, dan rencanakan waktu bersama tanpa terburu-buru.</p>
                    <span class="status" style="background:#eee7f8;color:#60469b">Resto pilihan lokal</span>
                    <span class="status" style="background:#eee7f8;color:#60469b">Reservasi lebih tenang</span>
                </div>
            </div>
        </div>

        <div class="col-lg-6 d-flex align-items-center">
            <div class="w-100 mx-auto auth-form">
                <h1 class="mb-2">Senang bertemu lagi.</h1>
                <p class="subtitle mb-4">Masuk dan lanjutkan rencana makan Anda.</p>

                <form method="POST" action="<?php echo e(route('login.process')); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label class="field-label">Email</label>
                        <input class="form-control" type="email" name="email" value="<?php echo e(old('email')); ?>" placeholder="nama@email.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="field-label">Password</label>
                        <div class="input-group">
                            <input id="loginPassword" class="form-control" type="password" name="password" placeholder="Masukkan Password Anda" required>
                            <button class="btn btn-outline-purple" type="button" onclick="togglePassword('loginPassword',this)" aria-label="Tampilkan password"><i class="bi bi-eye-slash"></i></button>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <label><input type="checkbox" name="remember" class="form-check-input me-1"> Ingat saya</label>
                        <span class="subtitle small">Gunakan email dan password akun Anda.</span>
                    </div>
                    <button class="btn btn-purple w-100"><i class="bi bi-arrow-right me-2"></i>Masuk</button>
                </form>

                <p class="mt-3 subtitle">Belum punya akun? <a href="<?php echo e(route('register')); ?>" class="fw-bold text-decoration-none" style="color:var(--purple)">Daftar sekarang</a></p>
            </div>
        </div>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>function togglePassword(id,button){const input=document.getElementById(id);input.type=input.type==='password'?'text':'password';button.innerHTML=input.type==='password'?'<i class="bi bi-eye-slash"></i>':'<i class="bi bi-eye"></i>';}</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\Reservia_Kelompok4_B\resources\views/auth/login.blade.php ENDPATH**/ ?>