<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservasi extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_AKTIF = ['menunggu', 'dikonfirmasi', 'check_in'];

    public const MINIMAL_PER_ORANG = 50000;
    public const DURASI_JAM = 2;
    public const BATAS_DP_MENIT = 60;
    public const ALASAN_BATAL_OTOMATIS = 'Batal otomatis: bukti DP tidak diunggah dalam 1 jam.';

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

    public static function batalkanKedaluwarsa(): int
    {
        return static::where('status', 'menunggu')
            ->where('status_dp', 'belum_bayar')
            ->where('created_at', '<=', now()->subMinutes(self::BATAS_DP_MENIT))
            ->update([
                'status'           => 'dibatalkan',
                'alasan_penolakan' => self::ALASAN_BATAL_OTOMATIS,
            ]);
    }


    protected function kodeBooking(): Attribute
    {
        return Attribute::get(function () {
            $dibuat = ($this->created_at ?? now())->copy()->timezone('Asia/Jakarta');

            return 'RV-'.$dibuat->format('dmy').'-'.strtoupper(substr($this->id, -4));
        });
    }

    protected function bisaDiedit(): Attribute
    {
        return Attribute::get(fn () => $this->status === 'menunggu');
    }

    protected function bisaDibatalkan(): Attribute
    {
        return Attribute::get(fn () => in_array($this->status, ['menunggu', 'dikonfirmasi'], true));
    }

    protected function batalOtomatis(): Attribute
    {
        return Attribute::get(fn () => $this->status === 'dibatalkan'
            && $this->alasan_penolakan === self::ALASAN_BATAL_OTOMATIS);
    }

    protected function sisaBayar(): Attribute
    {
        return Attribute::get(function () {
            if ($this->status_pembayaran === 'lunas') {
                return 0;
            }

            if (in_array($this->status_dp, ['menunggu_verifikasi', 'diterima'], true)) {
                return max(0, $this->total_harga - $this->dp_nominal);
            }

            return $this->total_harga;
        });
    }

    protected function jamRentang(): Attribute
    {
        return Attribute::get(function () {
            $mulai = \Carbon\Carbon::createFromFormat('H:i', substr($this->jam, 0, 5));

            return $mulai->format('H.i').'–'.$mulai->copy()->addHours(self::DURASI_JAM)->format('H.i');
        });
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::get(function () {
            if ($this->batal_otomatis) {
                return 'Batal otomatis';
            }

            return match ($this->status) {
                'menunggu'     => 'Menunggu konfirmasi',
                'dikonfirmasi' => 'Dikonfirmasi',
                'check_in'     => 'Sudah check-in',
                'selesai'      => 'Selesai',
                'ditolak'      => 'Ditolak resto',
                'dibatalkan'   => 'Dibatalkan',
                default        => ucfirst(str_replace('_', ' ', $this->status)),
            };
        });
    }

    protected function dpLabel(): Attribute
    {
        return Attribute::get(function () {
            if ($this->batal_otomatis && $this->status_dp === 'belum_bayar') {
                return 'Tenggat terlewat';
            }

            return match ($this->status_dp) {
                'belum_bayar'         => 'Belum bayar',
                'menunggu_verifikasi' => 'Menunggu verifikasi',
                'diterima'            => 'Terverifikasi',
                'ditolak'             => 'Ditolak',
                default               => ucfirst(str_replace('_', ' ', $this->status_dp)),
            };
        });
    }

    protected function dpKelas(): Attribute
    {
        return Attribute::get(function () {
            if ($this->batal_otomatis && $this->status_dp === 'belum_bayar') {
                return 'status-ditolak';
            }

            return match ($this->status_dp) {
                'diterima' => 'status-dikonfirmasi',
                'ditolak'  => 'status-ditolak',
                default    => 'status-menunggu',
            };
        });
    }

    protected function dpCatatan(): Attribute
    {
        return Attribute::get(function () {
            $dp = 'DP Rp'.number_format($this->dp_nominal, 0, ',', '.');

            if ($this->batal_otomatis && $this->status_dp === 'belum_bayar') {
                return 'Tidak unggah dalam 1 jam';
            }

            return match ($this->status_dp) {
                'belum_bayar'         => 'Unggah bukti '.$dp,
                'menunggu_verifikasi' => $dp,
                'diterima'            => $this->status_pembayaran === 'lunas' ? 'Pembayaran lunas' : $dp,
                'ditolak'             => 'Perlu unggah ulang',
                default               => '',
            };
        });
    }
}