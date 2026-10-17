<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resto extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function pemilik(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pemilik_id');
    }

    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }

    public function reservasis(): HasMany
    {
        return $this->hasMany(Reservasi::class);
    }

    public function kapasitasTerpakai(string $tanggal, string $jam): int
    {
        return (int) $this->reservasis()
            ->where('tanggal', $tanggal)
            ->where('jam', $jam)
            ->whereIn('status', Reservasi::STATUS_AKTIF)
            ->sum('jumlah_orang');
    }

    public function sisaKapasitas(string $tanggal, string $jam): int
    {
        return max(0, $this->kapasitas - $this->kapasitasTerpakai($tanggal, $jam));
    }

    public function statusKetersediaan(string $tanggal, string $jam): string
    {
        $sisa = $this->sisaKapasitas($tanggal, $jam);

        if ($sisa <= 0) {
            return 'penuh';
        }

        if ($sisa <= $this->kapasitas * 0.2) {
            return 'hampir_penuh';
        }

        return 'tersedia';
    }
}