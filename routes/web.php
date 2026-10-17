<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OwnerController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => Auth::check() ? redirect(Auth::user()->homeUrl()) : redirect()->route('login'));
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.process');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.process');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
Route::middleware('auth')->group(function () {
    Route::get('/resto', [CustomerController::class, 'index'])->name('customer.index')->middleware('can:customer-area');
    Route::get('/resto/{resto}', [CustomerController::class, 'show'])->name('customer.show')->middleware('can:customer-area');
    Route::get('/resto/{resto}/reservasi', [CustomerController::class, 'createReservation'])->name('customer.reserve')->middleware('can:customer-area');
    Route::post('/resto/{resto}/reservasi', [CustomerController::class, 'storeReservation'])->name('customer.reserve.store')->middleware('can:customer-area');
    Route::post('/reservasi/{reservasi}/bukti-dp', [CustomerController::class, 'uploadProof'])->name('customer.reservations.proof')->middleware('can:customer-area');
    Route::get('/reservasi/{reservasi}', [CustomerController::class, 'showReservation'])->name('customer.reservations.show')->middleware('can:customer-area');
    Route::put('/reservasi/{reservasi}', [CustomerController::class, 'updateReservation'])->name('customer.reservations.update')->middleware('can:customer-area');
    Route::delete('/reservasi/{reservasi}', [CustomerController::class, 'cancelReservation'])->name('customer.reservations.cancel')->middleware('can:customer-area');
    Route::get('/reservasi', [CustomerController::class, 'reservations'])->name('customer.reservations')->middleware('can:customer-area');
    Route::get('/profil', [CustomerController::class, 'profile'])->name('customer.profile')->middleware('can:customer-area');
    Route::put('/profil', [CustomerController::class, 'updateProfile'])->name('customer.profile.update')->middleware('can:customer-area');

    Route::middleware('can:owner-view')->prefix('pemilik')->group(function () {
        Route::get('/dashboard', [OwnerController::class, 'dashboard'])->name('owner.dashboard');
        Route::get('/dashboard/grafik', [OwnerController::class, 'chartData'])->name('owner.chart');
        Route::get('/resto', [OwnerController::class, 'restos'])->name('owner.restos');
        Route::get('/reservasi', [OwnerController::class, 'reservations'])->name('owner.reservations');
        Route::get('/reservasi/{reservasi}', [OwnerController::class, 'showReservation'])->name('owner.reservations.show');
    });

    Route::middleware('can:owner-area')->prefix('pemilik')->group(function () {
        Route::post('/resto', [OwnerController::class, 'storeResto'])->name('owner.restos.store');
        Route::put('/resto/{resto}', [OwnerController::class, 'updateResto'])->name('owner.restos.update');
        Route::patch('/resto/{resto}/toggle', [OwnerController::class, 'toggleResto'])->name('owner.restos.toggle');
        Route::delete('/resto/{resto}', [OwnerController::class, 'deleteResto'])->name('owner.restos.delete');

        Route::post('/menu', [OwnerController::class, 'storeMenu'])->name('owner.menus.store');
        Route::put('/menu/{menu}', [OwnerController::class, 'updateMenu'])->name('owner.menus.update');
        Route::delete('/menu/{menu}', [OwnerController::class, 'deleteMenu'])->name('owner.menus.delete');

        Route::patch('/reservasi/{reservasi}', [OwnerController::class, 'updateStatus'])->name('owner.reservasi.status');
        Route::patch('/reservasi/{reservasi}/dp', [OwnerController::class, 'updateDpStatus'])->name('owner.reservasi.dp');
    });

    Route::get('/admin/dashboard', fn () => redirect()->route('owner.dashboard'))->middleware('can:admin-area');
});