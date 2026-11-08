# Laporan QA-01: Data Awal, Unit Test Strategy, dan Uji Maintainability

| Item              | Isi                                                                     |
| ----------------- | ----------------------------------------------------------------------- |
| Penguji (QA)      | Aghni Ridfalah, 102012300296                                            |
| Tanggal uji       | 21 Oktober 2026                                                         |
| Issue Linear      | PAD-21 [QA-01]                                                          |
| Branch kerja      | `feature/PAD-21-qa-seeder-strategy-test`                                |
| Branch eksperimen | `feature/PAD-21-qa-auditorium-experiment`                               |
| Basis kode        | `main` commit `1c3b722`                                                 |
| Lingkungan        | Windows, PHP 8.3 (Laravel Herd), Laravel 13.36.0, database SQLite       |
| Kriteria rubrik   | Project II: Kemudahan pemeliharaan (15 poin) dan Struktur MVC (25 poin) |

## 1. Tujuan

1. Menyediakan data awal yang meniru kasus nyata peminjaman ruang di FRI.
2. Membuktikan perhitungan `ClassroomUtilizationStrategy` dan `LaboratoryUtilizationStrategy` benar dengan automated test.
3. Membuktikan fitur baru dapat ditambahkan dengan perubahan kode aplikasi paling banyak 2 file tanpa merusak fitur lama.

## 2. Data awal (DatabaseSeeder)

File: `database/seeders/DatabaseSeeder.php`. Data dibuat tetap (tidak acak) dan aman dijalankan berulang (`firstOrCreate`).

| Data                      | Jumlah | Keterangan                                                                                      |
| ------------------------- | ------ | ----------------------------------------------------------------------------------------------- |
| Unit akademik             | 3      | FRI, FIF, FTE                                                                                   |
| Pengguna                  | 7      | Test User, admin, kepala lab (`lab_head`), pimpinan (`leader`), 3 peminjam; password `password` |
| Ruang                     | 5      | 2 laboratorium (Lab Komputer, Lab Manufaktur), 3 ruang kelas, 1 di antaranya maintenance        |
| Fasilitas                 | 8      | Termasuk 1 rusak dan 2 maintenance                                                              |
| Jadwal                    | 5      | Minggu lalu                                                                                     |
| Booking `completed`       | 7      | Minggu lalu, lengkap dengan check-in dan check-out (ada yang telat dan pulang lebih awal)       |
| Ghost booking (`no_show`) | 3      | Disetujui tetapi peminjam tidak datang                                                          |
| Booking akan datang       | 3      | 1 `approved`, 1 `pending`, 1 `rejected`                                                         |

Perintah `php artisan migrate:fresh --seed` berjalan sukses: seluruh 12 migrasi `DONE`, lalu `Seeding database.` tanpa error.

## 3. Unit test Strategy

File: `tests/Unit/UtilizationStrategyTest.php` (11 test) dan `tests/Feature/DatabaseSeederTest.php` (6 test).
Minggu uji: Senin 5 s.d. Jumat 9 Oktober 2026, jam tersedia 5 hari x 10 jam (07.00-17.00) = 50 jam.

| Test                                                                                                                      | Data uji                                                      | Hitungan manual                                             | Hasil |
| ------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------- | ----------------------------------------------------------- | ----- |
| Kelas: jam terjadwal, aktual, ghost                                                                                       | 2 sesi dipakai (2 + 3 jam), 1 ghost (2 jam), 1 jadwal (3 jam) | terjadwal 10/50 = 20%; aktual 5/50 = 10%; ghost 1/3 = 33,3% | Lulus |
| Kelas: realisasi dan okupansi kursi                                                                                       | 20 dan 30 peserta di ruang 40 kursi                           | realisasi 5/7 = 71,4%; okupansi rata-rata(50%, 75%) = 62,5% | Lulus |
| Kelas: penuh setiap hari                                                                                                  | 9 jam x 5 hari                                                | 45/50 = 90%, overutilized                                   | Lulus |
| Di luar jam operasional                                                                                                   | booking 16.00-19.00                                           | hanya 1 jam dihitung = 2%                                   | Lulus |
| Pending dan rejected                                                                                                      | 2 booking                                                     | tidak dihitung                                              | Lulus |
| Lab: setup dan penalti ghost                                                                                              | 3 sesi (10 jam), 1 ghost (2 jam)                              | (10 + 1,5 - 1) / 50 = 21%                                   | Lulus |
| Lab: hanya ghost                                                                                                          | 1 ghost                                                       | tidak negatif, 0%                                           | Lulus |
| Data sama, jenis ruang beda                                                                                               | data lab di atas                                              | kelas 20%, lab 21%                                          | Lulus |
| Context memilih strategy                                                                                                  | classroom, laboratory                                         | strategy yang sesuai                                        | Lulus |
| Interface sama                                                                                                            | semua strategy terdaftar                                      | memakai `UtilizationStrategyInterface`                      | Lulus |
| Tipe belum didukung                                                                                                       | `kolam-renang`                                                | ditolak (`InvalidArgumentException`)                        | Lulus |
| Seeder (6 test): data master, tipe ruang, status booking, dijalankan 2 kali, semua halaman terbuka, dashboard minggu lalu | data seeder                                                   | sesuai tabel bagian 2                                       | Lulus |

Hasil `php artisan test`:

| Kondisi                                | Hasil                                   |
| -------------------------------------- | --------------------------------------- |
| `main` sebelum pekerjaan QA (baseline) | 14 passed                               |
| Setelah seeder dan test QA             | **31 passed (109 assertions)**, 0 gagal |

## 4. Pemeriksaan struktur MVC

| Pemeriksaan                                                                              | Hasil | Catatan                                                                          |
| ---------------------------------------------------------------------------------------- | ----- | -------------------------------------------------------------------------------- |
| Controller tipis: tidak berisi rumus utilisasi atau query mentah                         | Lolos | Logika ada di `app/Services` (`BookingService`, `UtilizationDashboardService`)   |
| View (`resources/views`) tanpa query atau perhitungan                                    | Lolos | Tidak ditemukan `DB::`, `where(`, `find`, atau `all()` di view                   |
| Rumus utilisasi berada di `app/Strategies`, dipilih lewat `UtilizationCalculatorContext` | Lolos | Didaftarkan di `AppServiceProvider`                                              |
| Semua halaman lewat `routes/web.php` dan dapat dibuka dengan data seeder                 | Lolos | Test `test_semua_halaman_bisa_dibuka_dengan_data_seeder` (7 halaman, status 200) |

## 5. Uji maintainability: menambah jenis ruang Auditorium

### 5.1 Sebelum perubahan

Branch `feature/PAD-21-qa-seeder-strategy-test`: `php artisan test` -> **31 passed, 0 gagal.**

### 5.2 Perubahan

Fitur baru: perhitungan utilisasi ruang **Auditorium**. Jam pemakaian ditimbang dengan okupansi kursi (jam x peserta/kapasitas), karena auditorium besar yang dipakai sedikit orang sebenarnya kurang termanfaatkan.

Output `git diff --cached --stat feature/PAD-21-qa-seeder-strategy-test`:

```
 app/Providers/AppServiceProvider.php             |  1 +
 app/Strategies/AuditoriumUtilizationStrategy.php | 57 +++++
 tests/Unit/AuditoriumUtilizationStrategyTest.php | 80 +++++
 3 files changed, 138 insertions(+)
```

| No  | File                                               | Jenis           | Isi perubahan                                                      | Dihitung untuk rubrik? |
| --- | -------------------------------------------------- | --------------- | ------------------------------------------------------------------ | ---------------------- |
| 1   | `app/Strategies/AuditoriumUtilizationStrategy.php` | Baru            | Strategy baru turunan `AbstractUtilizationStrategy`                | Ya (kode aplikasi)     |
| 2   | `app/Providers/AppServiceProvider.php`             | Diubah, 1 baris | Mendaftarkan strategy auditorium ke `UtilizationCalculatorContext` | Ya (kode aplikasi)     |
| 3   | `tests/Unit/AuditoriumUtilizationStrategyTest.php` | Baru            | Test untuk fitur baru                                              | Tidak (file test)      |

**Total file kode aplikasi yang berubah: 2.** Tidak disentuh: interface, strategy kelas dan lab, context, model, controller, view, migration, dan file test lama.

### 5.3 Sesudah perubahan

`php artisan test` -> **33 passed (114 assertions), 0 gagal.**

| Pemeriksaan                | Sebelum  | Sesudah  | Status                    |
| -------------------------- | -------- | -------- | ------------------------- |
| Test lama                  | 31 lulus | 31 lulus | Fitur lama tetap jalan    |
| Test baru (auditorium)     | -        | 2 lulus  | Fitur baru berfungsi      |
| `migrate:fresh --seed`     | Sukses   | Sukses   | Tidak terpengaruh         |
| File kode aplikasi berubah | -        | 2        | Memenuhi syarat <= 2 file |

## 6. Kesimpulan

Penambahan jenis ruang Auditorium hanya membutuhkan 1 file strategy baru dan 1 baris pendaftaran di `AppServiceProvider`. Seluruh 31 test lama tetap lulus. Hal ini dimungkinkan karena setiap rumus utilisasi dipisahkan ke kelas strategy yang memakai satu interface, dan `UtilizationCalculatorContext` memilih strategy berdasarkan tipe ruang tanpa mengenal kelas konkretnya.

## 7. Temuan

| No  | Temuan                                                                                                                  | Dampak                                                                                                                                             | Saran                                                                    |
| --- | ----------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------ |
| 1   | Kolom `type` tidak ada di `$fillable` model `Room`                                                                      | Tipe ruang tidak bisa diisi lewat form atau `Room::create`; semua ruang baru otomatis `classroom`. Seeder memakai `forceFill` sebagai jalan keluar | BE menambah `'type'` ke `$fillable` dan ke form tambah ruang             |
| 2   | File cache `bootstrap/cache/packages.php` dan `services.php` ikut ter-commit, dan `bootstrap/cache/.gitignore` terhapus | File cache dibuat ulang saat `composer install`, sehingga `git pull` di laptop anggota lain bisa gagal (konflik)                                   | Kembalikan `bootstrap/cache/.gitignore`, hapus kedua file cache dari Git |

## 8. Pemakaian AI

Seeder, unit test, strategy auditorium, dan kerangka laporan ini disusun dengan bantuan Claude (Anthropic). QA menjalankan seluruh perintah, memeriksa hasil test, dan memastikan kode sesuai dengan kode tim di `main`. Keluaran AI yang diperbaiki: (1) paket versi pertama tidak dipakai karena tim sudah membuat strategy sendiri, sehingga test disesuaikan dengan kode tim; (2) test seeder awalnya mengharapkan 6 booking `completed`, diperbaiki menjadi 7 sesuai data seeder.
