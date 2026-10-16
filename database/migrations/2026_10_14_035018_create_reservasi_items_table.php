<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservasi_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reservasi_id')->constrained('reservasis')->cascadeOnDelete();
            $table->foreignUuid('menu_id')->constrained('menus');
            $table->unsignedInteger('jumlah');
            $table->unsignedInteger('harga_satuan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservasi_items');
    }
};