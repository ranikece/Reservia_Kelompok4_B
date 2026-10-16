<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservasi extends Model
{
    use HasFactory, HasUuids;

    // Status yang dihitung sebagai kapasitas terpakai
    public const STATUS_AKTIF = ['menunggu', 'dikonfirmasi', 'check_in'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resto(): BelongsTo
    {
        return $this->belongsTo(Resto::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReservasiItem::class);
    }
}