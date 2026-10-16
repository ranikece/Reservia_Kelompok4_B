<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Menu extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tersedia' => 'boolean',
        ];
    }

    public function resto(): BelongsTo
    {
        return $this->belongsTo(Resto::class);
    }
}