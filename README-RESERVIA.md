# Reservia — aplikasi reservasi restoran

## Persiapan lokal (Windows/Laragon)
1. Ekstrak folder `reservia` ke `C:\laragon\www\reservia` (atau folder web lokal lain).
2. Buka terminal di folder project.
3. Jika `.env` belum ada, jalankan `Copy-Item .env.example .env`.
4. Jalankan `php artisan key:generate`.
5. Pastikan `.env` memakai `DB_CONNECTION=sqlite` dan file `database/database.sqlite` ada.
6. Jalankan `php artisan migrate:fresh --seed`. Perintah ini menghapus lalu membuat ulang database; jangan dipakai jika ingin mempertahankan data lama.
7. Jalankan `php artisan storage:link`.
8. Jalankan `php artisan serve`, lalu buka `http://127.0.0.1:8000`.

## Akun demo
Semua akun memakai password `password123`.
- Admin: `admin@reservia.test`
- Pemilik: `pemilik1@reservia.test`
- Pemilik kedua: `pemilik2@reservia.test`
- Pelanggan: `pelanggan@reservia.test`

Pendaftaran publik selalu membuat role pelanggan. Role admin/pemilik hanya tersedia untuk akun demo atau dibuat secara langsung melalui database oleh pengelola.

Database default memakai SQLite agar mudah dijalankan. Struktur database disediakan melalui file migration Laravel; jalankan seeder untuk data demo.
