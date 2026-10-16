<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservasis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('resto_id')->constrained('restos')->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('jam');
            $table->unsignedInteger('jumlah_orang');
            $table->text('catatan')->nullable();
            $table->string('status')->default('menunggu');
            $table->unsignedInteger('total_harga')->default(0);
            $table->unsignedInteger('dp_nominal')->default(0);
            $table->string('bukti_dp')->nullable();
            $table->string('status_dp')->default('belum_bayar');
            $table->string('status_pembayaran')->default('belum_lunas');
            $table->text('alasan_penolakan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservasis');
    }
};