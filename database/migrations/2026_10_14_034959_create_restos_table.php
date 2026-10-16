<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pemilik_id')->constrained('users')->cascadeOnDelete();
            $table->string('nama');
            $table->text('alamat');
            $table->string('kota');
            $table->unsignedInteger('kapasitas');
            $table->time('jam_buka');
            $table->time('jam_tutup');
            $table->text('fasilitas')->nullable();
            $table->string('foto')->nullable();
            $table->unsignedTinyInteger('dp_persen')->default(30);
            $table->string('nama_bank')->nullable();
            $table->string('no_rekening')->nullable();
            $table->string('atas_nama')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restos');
    }
};