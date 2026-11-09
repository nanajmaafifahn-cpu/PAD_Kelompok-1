# Campus Space & Laboratory Utilization System

Sistem peminjaman ruang kelas dan laboratorium berbasis Laravel untuk Fakultas Rekayasa Industri. Fitur utama:
jelajah ruangan, jadwal mingguan, pengajuan dan persetujuan peminjaman, check-in/check-out lewat QR ruangan,
deteksi *ghost booking* (peminjam tidak datang), dan Laporan Utilisasi per ruangan untuk manajemen kampus.

Dikembangkan oleh **PAD – Kelompok 1**.

---

## Daftar Isi

1. [Prasyarat](#1-prasyarat)
2. [Instalasi langkah demi langkah (XAMPP)](#2-instalasi-langkah-demi-langkah-xampp)
3. [Menyiapkan database: pilih Cara A atau Cara B](#3-menyiapkan-database-pilih-cara-a-atau-cara-b)
4. [Menjalankan aplikasi](#4-menjalankan-aplikasi)
5. [Akun demo](#5-akun-demo)
6. [Memeriksa instalasi berhasil](#6-memeriksa-instalasi-berhasil)
7. [Mengatasi masalah (Troubleshooting)](#7-mengatasi-masalah-troubleshooting)
8. [Struktur folder penting](#8-struktur-folder-penting)
9. [Dokumentasi lain](#9-dokumentasi-lain)

---

## 1. Prasyarat

| Perangkat lunak | Versi minimum | Keterangan |
|---|---|---|
| **XAMPP** | dengan **PHP 8.3** atau lebih baru | Dipakai untuk MySQL/MariaDB dan PHP. XAMPP dengan PHP 8.2 ke bawah **tidak bisa** dipakai karena proyek memakai Laravel 13. |
| **Composer** | 2.x | <https://getcomposer.org/download/> — saat instalasi, arahkan ke `C:\xampp\php\php.exe`. |
| **Node.js** | 20 LTS atau lebih baru (disarankan 22) | Untuk membangun CSS/JS dengan Vite. <https://nodejs.org/> |
| **Git** | apa saja | Untuk meng-*clone* repository. |

Cek versi lewat Command Prompt / PowerShell / terminal VS Code:

```bash
php -v          # harus PHP 8.3.x atau lebih baru
composer -V
node -v
npm -v
```

> Jika `php -v` tidak dikenali atau versinya bukan PHP dari XAMPP, tambahkan `C:\xampp\php` ke **Environment
> Variables → Path** Windows, lalu tutup dan buka lagi terminal.

### Ekstensi PHP yang harus aktif

Buka `C:\xampp\php\php.ini`, pastikan baris berikut **tidak** diawali titik koma (`;`):

```ini
extension=fileinfo
extension=gd
extension=mbstring
extension=openssl
extension=pdo_mysql
extension=zip
```

Simpan file, lalu restart Apache/MySQL dari XAMPP Control Panel. `fileinfo` dan `gd` dibutuhkan untuk unggah foto ruangan.

---

## 2. Instalasi langkah demi langkah (XAMPP)

### Langkah 1 — Clone repository

```bash
git clone https://github.com/yardan1204/PAD_Kelompok-1.git
cd PAD_Kelompok-1
```

> Folder proyek **tidak harus** berada di `C:\xampp\htdocs`, karena aplikasi dijalankan dengan `php artisan serve`
> (lihat bagian 4). XAMPP cukup dipakai untuk MySQL.

### Langkah 2 — Instal dependensi PHP

```bash
composer install
```

Perintah ini membuat folder `vendor/`. Proses pertama bisa memakan waktu beberapa menit.

### Langkah 3 — Buat file `.env`

Windows (Command Prompt / PowerShell):

```bash
copy .env.example .env
```

macOS / Linux / Git Bash:

```bash
cp .env.example .env
```

### Langkah 4 — Buat application key

```bash
php artisan key:generate
```

Baris `APP_KEY=` di `.env` akan terisi otomatis. Tanpa langkah ini, aplikasi menampilkan error *"No application encryption key has been specified"*.

### Langkah 5 — Periksa konfigurasi database di `.env`

Nilai bawaan `.env.example` sudah cocok dengan XAMPP standar (user `root` tanpa password):

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=campus_space_xampp
DB_USERNAME=root
DB_PASSWORD=
```

Sesuaikan `DB_USERNAME`/`DB_PASSWORD` bila MySQL di XAMPP Anda memakai password, atau `DB_PORT` bila MySQL berjalan
di port lain (misalnya `3307`).

Pengaturan lain di `.env` yang perlu diketahui:

| Variabel | Nilai bawaan | Fungsi |
|---|---|---|
| `APP_URL` | `http://localhost:8000` | Dipakai untuk URL foto ruangan dan URL di dalam QR ruangan. Samakan dengan alamat yang dibuka di browser. Agar QR bisa dipindai dari HP, ganti dengan IP laptop di jaringan yang sama, misalnya `http://192.168.1.10:8000`. |
| `APP_TIMEZONE` | `Asia/Jakarta` | Zona waktu jam operasional (07.00–17.00) dan toleransi *ghost booking*. |
| `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` | `database` | Memakai tabel `sessions`, `cache`, `jobs` yang sudah ada di skrip SQL dan migrasi. |

### Langkah 6 — Nyalakan MySQL di XAMPP

Buka **XAMPP Control Panel** → klik **Start** pada baris **MySQL** (Apache opsional, hanya perlu untuk phpMyAdmin).

### Langkah 7 — Siapkan database

Lanjut ke [bagian 3](#3-menyiapkan-database-pilih-cara-a-atau-cara-b) dan pilih **salah satu** cara.

### Langkah 8 — Instal dan bangun aset front-end

```bash
npm install
npm run build
```

Perintah ini membangun Tailwind CSS, `resources/css/campus-space.css`, dan JavaScript (termasuk pemindai QR) ke
`public/build/`.

> Bila langkah ini dilewati, layout tetap tampil karena ada *fallback* Tailwind CDN (butuh internet), tetapi
> JavaScript tidak dimuat sama sekali: menu samping di layar HP, **pindai QR**, dan **cetak QR** tidak berfungsi.
> Jadi tetap jalankan langkah ini.

### Langkah 9 — Tautkan folder penyimpanan foto

```bash
php artisan storage:link
```

Membuat tautan `public/storage` → `storage/app/public` agar foto ruangan yang diunggah bisa ditampilkan.

### Langkah 10 — Bersihkan cache konfigurasi

```bash
php artisan config:clear
```

Selesai. Lanjut ke [bagian 4](#4-menjalankan-aplikasi).

---

## 3. Menyiapkan database: pilih Cara A atau Cara B

Kedua cara menghasilkan database `campus_space_xampp` dengan 14 tabel dan data demo. Pilih **satu** saja.

| | Cara A — Impor `.sql` | Cara B — Migrasi + seeder |
|---|---|---|
| Alat | phpMyAdmin | Terminal (`php artisan`) |
| Tanggal data demo | **Tetap**: minggu 26–30 Okt 2026 dan 2–9 Nov 2026 | **Relatif terhadap hari ini**: minggu lalu dan minggu berjalan |
| Cocok untuk | Penilaian / demo dengan angka yang bisa dicocokkan dengan laporan | Demo langsung (jadwal, booking hari ini, *ghost booking* terbaru) |

### Cara A — Impor `database/campus_space_xampp.sql` lewat phpMyAdmin

1. Pastikan **Apache** dan **MySQL** di XAMPP sudah **Start**.
2. Buka <http://localhost/phpmyadmin>.
3. **Jangan** membuat database secara manual. Skrip sudah berisi `CREATE DATABASE IF NOT EXISTS campus_space_xampp`.
4. Klik tab **Import** (di halaman utama phpMyAdmin, bukan di dalam database tertentu).
5. **Choose File** → pilih `database/campus_space_xampp.sql` dari folder proyek.
6. Biarkan *Format* = **SQL**, lalu klik **Import** (atau **Go**).
7. Muncul pesan *"Import has been successfully finished"*. Di panel kiri muncul database `campus_space_xampp` berisi 14 tabel:
   `academic_units`, `bookings`, `cache`, `cache_locks`, `facilities`, `failed_jobs`, `job_batches`, `jobs`,
   `migrations`, `password_reset_tokens`, `rooms`, `schedules`, `sessions`, `users`.

Alternatif lewat terminal (tanpa phpMyAdmin), dari folder proyek:

```bash
C:\xampp\mysql\bin\mysql.exe -u root < database\campus_space_xampp.sql
```

> Tabel `migrations` di skrip sudah berisi ke-15 migrasi, jadi **tidak perlu** menjalankan `php artisan migrate` setelah impor.

**Isi data demo (Cara A):**

| Data | Jumlah |
|---|---|
| Unit akademik | 3 (FRI, FIF, FTE) |
| Pengguna | 5 akun demo (lihat bagian 5) |
| Ruangan | 19 (10 laboratorium, 9 ruang kelas; 2 di antaranya berstatus *maintenance*) |
| Fasilitas | per ruangan, termasuk yang rusak/*maintenance* |
| Jadwal kuliah/praktikum | 20 |
| Booking | 21 (semua status: `pending`, `approved`, `rejected`, `cancelled`, `checked_in`, `completed`, `no_show`) |

### Cara B — `php artisan migrate:fresh --seed`

Pastikan database kosong `campus_space_xampp` sudah ada. Bila belum, buat lewat phpMyAdmin (**New** → nama
`campus_space_xampp` → *Collation* `utf8mb4_unicode_ci` → **Create**), lalu jalankan:

```bash
php artisan migrate:fresh --seed
```

Hasil yang diharapkan: ke-15 migrasi berstatus `DONE`, lalu `Seeding database.` tanpa error.

> ⚠️ `migrate:fresh` **menghapus semua tabel** di database tujuan lalu membuat ulang. Jangan jalankan pada database yang datanya ingin disimpan.

---

## 4. Menjalankan aplikasi

```bash
php artisan serve
```

Buka <http://localhost:8000> di browser. Biarkan terminal tetap terbuka selama aplikasi dipakai; hentikan dengan `Ctrl + C`.

Untuk pengembangan front-end (CSS/JS langsung diperbarui tanpa build ulang), buka terminal kedua:

```bash
npm run dev
```

Menjalankan *test* otomatis (opsional, untuk anggota QA):

```bash
php artisan test
```

---

## 5. Akun demo

Semua akun memakai kata sandi **`password`**. Hanya untuk pengembangan lokal.

| Email | Nama | Role | Hak akses utama |
|---|---|---|---|
| `mahasiswa@campus.test` | Andi Pratama (Mahasiswa) | `borrower` | Jelajahi ruangan, ajukan peminjaman, Peminjaman Saya, pindai QR (check-in/out) |
| `dosen@campus.test` | Dr. Sinta Rahmawati (Dosen) | `borrower` | Sama dengan mahasiswa |
| `pengelola@campus.test` | Kepala Laboratorium FRI | `lab_head` | Setujui/tolak peminjaman, kelola ruangan, fasilitas, jadwal, cetak QR, Laporan Utilisasi |
| `admin@campus.test` | Admin Akademik FRI | `admin` | Semua hak pengelola + kelola unit akademik dan pengguna |
| `pimpinan@campus.test` | Wakil Dekan II FRI | `leader` | Dashboard dan Laporan Utilisasi (baca saja) |

---

## 6. Memeriksa instalasi berhasil

Ikuti daftar ini dari atas. Bila semua centang, instalasi sudah benar.

- [ ] <http://localhost:8000> menampilkan halaman **login** dengan tampilan yang sudah ber-*style*.
- [ ] Login sebagai `mahasiswa@campus.test` → muncul **Dashboard** peminjam.
- [ ] Menu **Jelajahi Ruangan** menampilkan daftar ruangan (19 ruangan bila memakai data demo).
- [ ] Logout, login sebagai `pengelola@campus.test` → menu **Laporan Utilisasi** tampil dengan tabel per ruangan.
- [ ] Di **Laporan Utilisasi**, ubah periode ke **26 Okt 2026 – 1 Nov 2026** (Cara A). Lab Komputer 1 harus
      tercatat 2 *ghost booking*.
- [ ] Menu **Cetak QR Ruangan** menampilkan gambar QR (artinya `npm run build` berhasil).

---

## 7. Mengatasi masalah (Troubleshooting)

| Gejala / pesan error | Penyebab | Solusi |
|---|---|---|
| `Your Composer dependencies require a PHP version ">= 8.3.0"` | PHP XAMPP masih 8.2 atau lebih lama | Instal XAMPP versi dengan PHP 8.3+, atau pastikan `php -v` menunjuk ke PHP 8.3. |
| `'php' is not recognized as an internal or external command` | PHP belum ada di PATH | Tambahkan `C:\xampp\php` ke Environment Variables → Path, lalu buka ulang terminal. |
| `composer install` gagal dengan `ext-fileinfo` / `ext-zip` / `ext-gd` *missing* | Ekstensi PHP belum aktif | Aktifkan di `php.ini` (lihat bagian 1), lalu ulangi. |
| `SQLSTATE[HY000] [2002] No connection could be made because the target machine actively refused it` | MySQL belum jalan, atau port salah | Start MySQL di XAMPP. Cek port di XAMPP (kolom *Port(s)*) dan samakan dengan `DB_PORT`. |
| `SQLSTATE[HY000] [1049] Unknown database 'campus_space_xampp'` | Database belum dibuat | Impor `.sql` (Cara A), atau buat database kosong dulu (Cara B). |
| `SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost'` | Password MySQL tidak cocok | Isi `DB_PASSWORD` sesuai password MySQL Anda, lalu `php artisan config:clear`. |
| phpMyAdmin: `#1050 - Table 'academic_units' already exists` saat impor ulang | Database lama masih ada (skrip tidak memuat `DROP TABLE`) | Di phpMyAdmin pilih database `campus_space_xampp` → tab **Operations** → **Drop the database**, lalu impor lagi. |
| phpMyAdmin: *"You probably tried to upload a file that is too large"* | Batas unggah PHP terlalu kecil | Naikkan `upload_max_filesize` dan `post_max_size` (mis. `64M`) di `php.ini`, restart Apache; atau impor lewat `mysql.exe` (lihat Cara A). |
| MySQL di XAMPP langsung berhenti setelah Start | Port 3306 dipakai layanan MySQL lain | Hentikan layanan MySQL lain di *Services* Windows, atau ubah port XAMPP lalu sesuaikan `DB_PORT`. |
| `No application encryption key has been specified` | Langkah 4 terlewat | `php artisan key:generate`, lalu `php artisan config:clear`. |
| Menu samping tidak bisa dibuka di layar HP, kamera pindai QR tidak muncul, gambar QR kosong | JavaScript belum dibangun (layout memakai *fallback* CSS saja) | `npm install` lalu `npm run build`. |
| Tampilan berantakan atau tanpa *style* | Aset belum dibangun dan tidak ada internet untuk *fallback* CDN | `npm run build`. |
| Foto ruangan tidak tampil (gambar rusak) | `storage:link` belum dijalankan, atau `APP_URL` tidak cocok | `php artisan storage:link`; samakan `APP_URL` dengan alamat di browser (mis. `http://localhost:8000`). |
| Error **419 Page Expired** saat submit form | Sesi kedaluwarsa / tabel `sessions` belum ada | Muat ulang halaman lalu login lagi. Pastikan tabel `sessions` ada (sudah termasuk di skrip SQL dan migrasi). |
| Perubahan `.env` tidak berpengaruh | Konfigurasi ter-*cache* | `php artisan config:clear` (dan `php artisan cache:clear`). |
| QR di HP membuka `localhost` dan gagal | `APP_URL` masih `localhost` | Isi `APP_URL` dengan IP laptop (`http://192.168.x.x:8000`), jalankan `php artisan serve --host=0.0.0.0`, pastikan HP di Wi-Fi yang sama. |
| Laporan Utilisasi semua 0% pada minggu berjalan (Cara A) | Data demo `.sql` bertanggal tetap (Okt–Nov 2026) | Pilih periode **26 Okt – 1 Nov 2026** di filter, atau pakai Cara B agar data mengikuti tanggal hari ini. |

---

## 8. Struktur folder penting

```
app/
  Http/Controllers/        Controller (Room, Booking, Schedule, Scan, Dashboard, UtilizationDashboard, Admin/*)
  Http/Requests/           Validasi form di sisi server
  Models/                  Model Eloquent (Room, Booking, Schedule, Facility, AcademicUnit, User)
  Services/                Logika bisnis (BookingService, UtilizationDashboardService, ...)
  Strategies/              Rumus utilisasi: ClassroomUtilizationStrategy, LaboratoryUtilizationStrategy
  Support/Utilization/     UtilizationPeriod (jam operasional 07.00–17.00, Senin–Jumat), UtilizationResult
database/
  campus_space_xampp.sql   Skrip SQL siap impor (struktur + data demo)
  migrations/              15 migrasi
  seeders/                 DatabaseSeeder, DemoAccountSeeder, DemoCampusSeeder
resources/
  css/campus-space.css     Stylesheet utama (CSS variables untuk warna status ruang)
  views/                   Blade (layouts, dashboard, rooms, bookings, schedules, scan, admin)
docs/                      Dokumentasi proyek (lihat bagian 9)
```

---

## 9. Dokumentasi lain

| Dokumen | Isi |
|---|---|
| [`docs/ROLE-PENGGUNA.md`](docs/ROLE-PENGGUNA.md) | Pembagian 4 role (Borrower, Lab Head, Admin, Pimpinan) beserta hak akses dan menu masing-masing |
| [`docs/ANALISIS-TEKNIK-INDUSTRI.md`](docs/ANALISIS-TEKNIK-INDUSTRI.md) | Studi kasus Teknik Industri: interpretasi utilisasi ruang, ruang *underutilized*/*overutilized*, dampak *ghost booking*, rekomendasi untuk manajemen kampus |
| [`docs/wireframe/README.md`](docs/wireframe/README.md) | Wireframe seluruh halaman (desktop dan mobile) beserta penjelasannya |
| [`docs/fe/KONTRAK-VIEW.md`](docs/fe/KONTRAK-VIEW.md) | Kontrak data antara Controller dan view Blade |
| [`docs/QA_MAINTAINABILITY_REPORT.md`](docs/QA_MAINTAINABILITY_REPORT.md) | Laporan QA: data awal, unit test Strategy, uji *maintainability* |
| [`docs/ai-usage/`](docs/ai-usage/) | Catatan penggunaan AI selama pengembangan |

---

Dibangun dengan [Laravel](https://laravel.com) (lisensi MIT).
