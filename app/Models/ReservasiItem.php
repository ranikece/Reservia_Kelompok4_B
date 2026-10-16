<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservasiItem extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = ['id'];

    public function reservasi(): BelongsTo
    {
        return $this->belongsTo(Reservasi::class);
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }
}