<?php
namespace App\Providers;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}
    public function boot(): void
    {
        Gate::define('customer-area', fn($user) => $user->role === 'pelanggan');
        Gate::define('owner-area', fn($user) => $user->role === 'pemilik');
        Gate::define('admin-area', fn($user) => $user->role === 'admin');
        Gate::define('owner-view', fn($user) => in_array($user->role, ['pemilik', 'admin'], true));
    }
}