<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids;

    protected $fillable = [
        'name',
        'email',
        'no_hp',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'  => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // Relasi
    public function restos(): HasMany
    {
        return $this->hasMany(Resto::class, 'pemilik_id');
    }

    public function reservasis(): HasMany
    {
        return $this->hasMany(Reservasi::class);
    }

    // Helper role
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPemilik(): bool
    {
        return $this->role === 'pemilik';
    }

    public function isPelanggan(): bool
    {
        return $this->role === 'pelanggan';
    }

    public function homeUrl(): string
    {
        return match ($this->role) 
        {
            'admin'   => '/admin/dashboard',
            'pemilik' => '/pemilik/dashboard',
            default   => '/resto',
        };
    }
}