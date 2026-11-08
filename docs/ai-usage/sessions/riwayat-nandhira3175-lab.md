# Riwayat Penggunaan AI — nandhira3175-lab

## Identitas Anggota
- **Nama Lengkap (`user.name`)**: nandhira3175-lab
- **Email Git (`user.email`)**: nandhira3175@gmail.com
- **Pembaruan Terakhir**: 2026-11-08 16:07:08 +00:00

---

## Sesi AI-20261020-160711 — 2026-10-20 16:07:11 +00:00

- **Identitas Git**: nandhira3175-lab (`nandhira3175@gmail.com`)
- **Provider AI**: CodeGPT
- **Tujuan**: Membuat catatan perbaikan kode oleh AI atas juknis dan perubahan routes/rooms serta konstanta Booking

### Prompt Asli
> Buatkan catatan perbaikan kode (pov: kode ditulis manusia, diperbaiki AI) sesuai juknis: composer, migrate, .env, route web.php, public function; perubahan: create folder room/index untuk testing rooms, tambah konstanta STATUS_APPROVED dan GHOST_TOLERANCE_MINUTES di models/Booking.php

### Output / Respons AI
File docs/catatan-perbaikan-ai.md berisi juknis 5 poin, kasus error dashboard utilization, dan solusi penambahan konstanta di Booking.php

### Ringkasan Perubahan (Before - Work - After)
- **Before (Kondisi Awal)**: Konstanta Booking belum terdokumentasi; view rooms belum tercatat
- **Pekerjaan AI**: Membuat docs/catatan-perbaikan-ai.md; verifikasi konstanta di Booking.php baris 11/14 dan route rooms di web.php
- **After (Kondisi Akhir)**: Catatan perbaikan tersedia di docs/catatan-perbaikan-ai.md
- **Pengujian**: Tidak dijalankan (dokumentasi)

### File Terdampak
```text
M	PAD_KELOMPOK_1
M	app/Models/Booking.php
Untracked:
docs/ai-usage/sessions/riwayat-dhira-ai.md
docs/catatan-perbaikan-ai.md
```
Diff stat:
```text
 PAD_KELOMPOK_1         | Bin 143360 -> 143360 bytes
 app/Models/Booking.php |   2 ++
 2 files changed, 2 insertions(+)
```

---

## Sesi AI-20261108-155337 — 2026-11-08 15:53:37 +00:00

- **Identitas Git**: nandhira3175-lab (`nandhira3175@gmail.com`)
- **Provider AI**: GitHub - Copilot
- **Tujuan**: Menganalisis penyebab login demo gagal

### Prompt Asli
> kenapa ketika saya mau login menggunakan user na dan password yang di sudah ada tapi kok Email atau kata sandi salah. padahal saya masukin user name dan pw yang benar apakah ada issue di kodenya

### Output / Respons AI
Menemukan form dan backend login menerima email, bukan username; kredensial akun demo ialah admin@campus.test dengan password password. Tidak ditemukan indikasi bug; tes autentikasi lulus.

### Ringkasan Perubahan (Before - Work - After)
- **Before (Kondisi Awal)**: Kode login menerima email dan password; status database pengguna aktif saat ini belum diverifikasi.
- **Pekerjaan AI**: Memeriksa LoginController, LoginRequest, form login, seeder, dan tes autentikasi tanpa mengubah kode.
- **After (Kondisi Akhir)**: Validasi menggunakan email dan password; akun harus ada di database aktif dan belum dibatasi rate limiter.
- **Pengujian**: php artisan test --filter=AuthAccessTest: lulus, 13 tes dan 105 assertion.

### File Terdampak
```text
Untracked:
PAD_KELOMPOK_1
```

---

## Sesi AI-20261108-160708 — 2026-11-08 16:07:08 +00:00

- **Identitas Git**: nandhira3175-lab (`nandhira3175@gmail.com`)
- **Provider AI**: GitHub - Copilot
- **Tujuan**: Menyelesaikan checklist CRUD MySQL XAMPP BE-06

### Prompt Asli
> kerjakan to do list ini

### Output / Respons AI
Melengkapi konfigurasi dan panduan MySQL XAMPP, membuat export database siap impor dengan data demo dan FK eksplisit; CRUD yang telah tersedia teruji pada MariaDB lokal.

### Ringkasan Perubahan (Before - Work - After)
- **Before (Kondisi Awal)**: CRUD Room, Facility, Booking, dan Schedule sudah tersedia serta FK didefinisikan melalui migrations; .env.example masih SQLite dan belum ada database/campus_space_xampp.sql.
- **Pekerjaan AI**: Mengubah .env.example ke MySQL XAMPP, menambahkan instruksi README, mengekspor schema/data dari migrasi/seeder MySQL dan menegaskan aksi ON DELETE; menjalankan tes CRUD MySQL dan validasi impor/cascade/restrict, lalu membersihkan DB validasi.
- **After (Kondisi Akhir)**: Database/campus_space_xampp.sql siap diimpor; konfigurasi contoh dan panduan mengarahkan Laravel ke campus_space_xampp; CRUD serta FK diverifikasi di MariaDB.
- **Pengujian**: php artisan test --filter=CrudManagementTest pada MariaDB: 18 lulus, 143 assertion; seluruh migrations dan DatabaseSeeder lulus; impor SQL ulang: 14 tabel, 19 ruang, 21 booking; CASCADE/RESTRICT lulus; git diff --check bersih.

### File Terdampak
```text
warning: in the working copy of 'docs/ai-usage/sessions/riwayat-nandhira3175-lab.md', CRLF will be replaced by LF the next time Git touches it
M	.env.example
M	README.md
M	docs/ai-usage/CHANGELOG-AI.md
M	docs/ai-usage/PROMPT-LOG.md
M	docs/ai-usage/sessions/RIWAYAT-SEMUA-SESI.md
M	docs/ai-usage/sessions/riwayat-nandhira3175-lab.md
Untracked:
PAD_KELOMPOK_1
database/campus_space_xampp.sql
```
Diff stat:
```text
warning: in the working copy of 'docs/ai-usage/sessions/riwayat-nandhira3175-lab.md', CRLF will be replaced by LF the next time Git touches it
 .env.example                                       | 12 +++++-----
 README.md                                          | 20 ++++++++++++++++
 docs/ai-usage/CHANGELOG-AI.md                      |  1 +
 docs/ai-usage/PROMPT-LOG.md                        |  1 +
 docs/ai-usage/sessions/RIWAYAT-SEMUA-SESI.md       | 26 ++++++++++++++++++++
 docs/ai-usage/sessions/riwayat-nandhira3175-lab.md | 28 +++++++++++++++++++++-
 6 files changed, 81 insertions(+), 7 deletions(-)
```
