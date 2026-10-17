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
    Route::get('/pemilik/dashboard', [OwnerController::class, 'dashboard'])->name('owner.dashboard')->middleware('can:owner-area');
    Route::get('/pemilik/resto', [OwnerController::class, 'restos'])->name('owner.restos')->middleware('can:owner-area');
    Route::post('/pemilik/resto', [OwnerController::class, 'storeResto'])->name('owner.restos.store')->middleware('can:owner-area');
    Route::put('/pemilik/resto/{resto}', [OwnerController::class, 'updateResto'])->name('owner.restos.update')->middleware('can:owner-area');
    Route::delete('/pemilik/resto/{resto}', [OwnerController::class, 'deleteResto'])->name('owner.restos.delete')->middleware('can:owner-area');
    Route::post('/pemilik/menu', [OwnerController::class, 'storeMenu'])->name('owner.menus.store')->middleware('can:owner-area');
    Route::put('/pemilik/menu/{menu}', [OwnerController::class, 'updateMenu'])->name('owner.menus.update')->middleware('can:owner-area');
    Route::delete('/pemilik/menu/{menu}', [OwnerController::class, 'deleteMenu'])->name('owner.menus.delete')->middleware('can:owner-area');
    Route::patch('/pemilik/reservasi/{reservasi}', [OwnerController::class, 'updateStatus'])->name('owner.reservasi.status')->middleware('can:owner-area');
    Route::patch('/pemilik/reservasi/{reservasi}/dp', [OwnerController::class, 'updateDpStatus'])->name('owner.reservasi.dp')->middleware('can:owner-area');
    Route::get('/admin/dashboard', fn () => view('owner.dashboard', ['restos'=>collect(), 'reservasis'=>\App\Models\Reservasi::with(['user','resto'])->latest()->get()]))->middleware('can:admin-area');
});
