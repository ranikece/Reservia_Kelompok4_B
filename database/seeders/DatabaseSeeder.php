<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Resto;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ===== AKUN DEMO (password semua: password123) =====
        User::updateOrCreate(['email' => 'admin@reservia.test'], [
            'name' => 'Admin Reservia', 'password' => 'password123', 'role' => 'admin',
        ]);

        $pemilik1 = User::updateOrCreate(['email' => 'pemilik1@reservia.test'], [
            'name' => 'Budi Pemilik', 'password' => 'password123', 'role' => 'pemilik',
        ]);

        $pemilik2 = User::updateOrCreate(['email' => 'pemilik2@reservia.test'], [
            'name' => 'Sari Pemilik', 'password' => 'password123', 'role' => 'pemilik',
        ]);

        User::updateOrCreate(['email' => 'pelanggan@reservia.test'], [
            'name' => 'Andi Pelanggan', 'password' => 'password123', 'role' => 'pelanggan',
        ]);

        // ===== RESTO DAN MENU CONTOH =====
        $restos = [
            [
                'pemilik' => $pemilik1, 'nama' => 'Dapur Lampung', 'kota' => 'Bandar Lampung',
                'alamat' => 'Jl. Teuku Umar No. 10', 'kapasitas' => 50,
                'fasilitas' => 'Musala, Parkir luas, Ruang privat',
                'menus' => [
                    ['Seruit Ikan Patin', 'Makanan', 35000],
                    ['Nasi Liwet', 'Makanan', 25000],
                    ['Es Teh Manis', 'Minuman', 6000],
                ],
            ],
            [
                'pemilik' => $pemilik1, 'nama' => 'Kopi Senja', 'kota' => 'Bandar Lampung',
                'alamat' => 'Jl. Z.A. Pagar Alam No. 22', 'kapasitas' => 30,
                'fasilitas' => 'Wifi, Area outdoor',
                'menus' => [
                    ['Kopi Susu Gula Aren', 'Minuman', 22000],
                    ['Roti Bakar', 'Makanan', 18000],
                ],
            ],
            [
                'pemilik' => $pemilik2, 'nama' => 'Warung Nusantara', 'kota' => 'Metro',
                'alamat' => 'Jl. Sudirman No. 5', 'kapasitas' => 80,
                'fasilitas' => 'Musala, Parkir luas, Ruang rapat',
                'menus' => [
                    ['Paket Bukber (4 orang)', 'Paket', 150000],
                    ['Ayam Bakar', 'Makanan', 30000],
                    ['Es Jeruk', 'Minuman', 8000],
                ],
            ],
        ];

        foreach ($restos as $r) {
            $resto = Resto::firstOrCreate(['nama' => $r['nama']], [
                'pemilik_id'  => $r['pemilik']->id,
                'alamat'      => $r['alamat'],
                'kota'        => $r['kota'],
                'kapasitas'   => $r['kapasitas'],
                'jam_buka'    => '10:00',
                'jam_tutup'   => '22:00',
                'fasilitas'   => $r['fasilitas'],
                'dp_persen'   => 30,
                'nama_bank'   => 'BCA',
                'no_rekening' => '1234567890',
                'atas_nama'   => $r['pemilik']->name,
            ]);

            foreach ($r['menus'] as [$nama, $kategori, $harga]) {
                Menu::firstOrCreate(
                    ['resto_id' => $resto->id, 'nama' => $nama],
                    ['kategori' => $kategori, 'harga' => $harga]
                );
            }
        }
    }
}