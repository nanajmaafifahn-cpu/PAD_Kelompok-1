# Kontrak View ↔ Controller (FE-01 s.d. FE-06)

Dokumen ini untuk anggota **Back-End** dan **Front-End**. Semua view Blade hanya *menampilkan* data; seluruh query,
rumus, dan aturan bisnis ada di Controller/Model/Service. Agar view tidak error, Controller wajib
mengirim variabel dan menyediakan route sesuai tabel di bawah.

> **Diverifikasi terhadap kode:** branch `main` @ `1c3b722` (3 Nov 2026).
> Bagian 1–7 = kontrak yang **sudah berlaku di kode**. Bagian 8 = **usulan FE yang belum disepakati**;
> jangan diperlakukan sebagai kontrak sampai BE mengonfirmasi dan mengimplementasikannya.

## 1. Daftar file view

| File | Tugas | Dipanggil oleh |
|---|---|---|
| `layouts/app.blade.php` | FE-01 | semua halaman (`@extends('layouts.app')`) |
| `layouts/partials/flash.blade.php` | FE-01 | layout (notifikasi sukses/error) |
| `components/nav-link.blade.php` | FE-01 | layout (menu; hanya muncul jika route-nya ada) |
| `components/status-badge.blade.php` | FE-01 | badge status (warna via CSS `data-status`) |
| `components/progress-bar.blade.php` | FE-01 | bar persentase |
| `components/field-error.blade.php` | FE-05 | pesan galat server satu field (`<x-field-error for="nama_field" />`) |
| `dashboard/utilization.blade.php` | FE-01 | `UtilizationDashboardController@index` |
| `rooms/index.blade.php` | FE-02 | `RoomController@index` |
| `rooms/create.blade.php` | FE-02 | `RoomController@create` |
| `schedules/index.blade.php` | FE-03 | `ScheduleController@index` |
| `bookings/index.blade.php` | FE-03 | `BookingController@index` |
| `bookings/create.blade.php` | FE-03 | `BookingController@create` |
| `bookings/partials/actions/{status}.blade.php` | FE-03 | dipilih otomatis sesuai `$booking->status` (file yang ada: `pending`, `approved`, `checked_in`, `rejected`, `none`) |
| `rooms/partials/form.blade.php` | FE-06 | field form ruang, dipakai `rooms/create` dan `rooms/edit` |
| `rooms/edit.blade.php` | FE-06 | **BLOCKED** — menunggu `RoomController@edit` (BE-05) |
| `bookings/partials/form.blade.php` | FE-06 | field form booking, dipakai `bookings/create` dan `bookings/edit` |
| `bookings/edit.blade.php` | FE-06 | **BLOCKED** — menunggu `BookingController@edit` (BE-06) |
| `bookings/partials/manage.blade.php` | FE-06 | tombol Ubah / Batalkan / Hapus booking, tampil hanya bila route-nya ada |
| `components/route-form.blade.php` | FE-06 | form yang hanya dirender bila route terdaftar; HTTP method diambil dari definisi route |

Laporan utilisasi memakai halaman dashboard yang sama (ringkasan laporan, rekomendasi, tombol **Cetak Laporan** + gaya `@media print`);
belum ada route laporan terpisah (BE-11).

**Aturan FE-06 untuk fitur yang menunggu backend:** form/tombol Ubah, Hapus, dan Batalkan hanya dirender bila
`Route::has('<nama route>')` bernilai benar, dengan nama route sesuai usulan BE-05/BE-06 di bagian 8. Tidak ada form
dengan action palsu. Bila BE memakai nama route lain, view perlu disesuaikan (atau nama di bagian 8 diperbarui bersama).

## 2. Route yang tersedia (`routes/web.php`)

| Nama route | Method & URI | Controller@method | FormRequest | Akses saat ini |
|---|---|---|---|---|
| `home` | `GET /` | `UtilizationDashboardController@index` | `UtilizationDashboardRequest` | publik |
| `dashboard` | `GET /dashboard` | `UtilizationDashboardController@index` | `UtilizationDashboardRequest` | publik |
| `dashboard.utilization` | `GET /dashboard/utilization` | `UtilizationDashboardController@index` | `UtilizationDashboardRequest` | publik |
| `rooms.index` | `GET /rooms` | `RoomController@index` | – | publik |
| `rooms.create` | `GET /rooms/create` | `RoomController@create` | – | publik |
| `rooms.store` | `POST /rooms` | `RoomController@store` | `StoreRoomRequest` | publik |
| `schedules.index` | `GET /schedules` | `ScheduleController@index` | – | publik |
| `schedules.store` | `POST /schedules` | `ScheduleController@store` | `StoreScheduleRequest` | publik |
| `bookings.index` | `GET /bookings` | `BookingController@index` | – | publik |
| `bookings.create` | `GET /bookings/create` | `BookingController@create` | – | publik |
| `bookings.store` | `POST /bookings` | `BookingController@store` | `StoreBookingRequest` | publik (tanpa login dicatat atas user "Walk-in Guest") |
| `bookings.approve` | `POST\|PATCH /bookings/{booking}/approve` | `BookingController@approve` | `ApproveBookingRequest` | **wajib login** + role `lab_head`/`admin` |
| `bookings.reject` | `POST /bookings/{booking}/reject` | `BookingController@reject` | `RejectBookingRequest` | **wajib login** + role `lab_head`/`admin` |
| `bookings.checkin` | `POST\|PATCH /bookings/{booking}/check-in` | `BookingController@checkIn` | `QrScanRequest` | **wajib login** |
| `bookings.checkout` | `POST\|PATCH /bookings/{booking}/check-out` | `BookingController@checkOut` | `QrScanRequest` | **wajib login** |

- Form aksi cukup `method="POST"` + `@csrf`. Route `bookings.reject` **hanya POST**, jadi jangan tambahkan `@method('PATCH')` di form reject.
- Parameter `{booking}` memakai route model binding (`route('bookings.approve', $booking)`).
- **Belum ada route** untuk: edit/update/hapus ruang, edit/update/batal/hapus booking, edit/hapus jadwal, CRUD fasilitas, login.

## 3. Variabel per view

### `dashboard.utilization`
Data disusun oleh `UtilizationDashboardService::build()` dan dikirim apa adanya. Kirim header
`Accept: application/json` untuk mendapat data yang sama dalam bentuk JSON.

Query string (opsional): `start`, `end` (tanggal, `end >= start`). Tanpa keduanya, periode = minggu berjalan
dan jam yang belum terjadi tidak dihitung.

```php
[
    'period' => ['start' => 'Y-m-d', 'end' => 'Y-m-d', 'available_hours' => float],
    'summary' => [
        'total_rooms' => int, 'underutilized' => int, 'overutilized' => int,
        'occupied_now' => int, 'booked_now' => int, 'available_now' => int,
    ],
    'rooms' => [ // array, satu baris per ruang, urut kode ruang
        [
            'room_id' => int, 'room_code' => string, 'room_name' => string,
            'room_type' => 'classroom|laboratory',
            'available_hours' => float, 'scheduled_hours' => float, 'actual_hours' => float,
            'scheduled_rate' => float,     // persen, sudah dibulatkan 1 desimal
            'utilization_rate' => float,   // persen, rumus tergantung Strategy tipe ruang
            'approved_bookings' => int, 'ghost_bookings' => int, 'ghost_rate' => float,
            'category' => 'underutilized|normal|overutilized', // < 30% | 30–79,9% | >= 80%
            'metrics' => [...],            // lihat di bawah
            'live_status' => 'available|booked|occupied|maintenance',
            'academic_unit' => ?string,
        ],
    ],
    'underutilized' => ['KODE', ...], // daftar kode ruang
    'overutilized' => ['KODE', ...],
]
```

- `metrics` untuk `classroom`: `realization_ratio`, `seat_occupancy_ratio` (keduanya bisa `null`).
  Untuk `laboratory`: `setup_hours`, `ghost_penalty_hours`, `raw_actual_rate`.
- Nilai status dan kategori **huruf kecil**, bukan `OPTIMAL`/`UNDERUTILIZED` seperti di versi lama dokumen ini.
- `summary` belum menghitung ruang berstatus live `maintenance`.
- Rata-rata utilisasi dan total ghost booking saat ini masih dihitung di view (`@php`), belum dikirim Service.
- Dashboard memuat ulang sendiri tiap 30 detik (`<meta http-equiv="refresh">`).

### `rooms.index`
- `$rooms` → `Room::with(['academicUnit', 'facilities'])->orderBy('name')->paginate(10)`.
- View memakai getter Model: `getEntitySummary()`, `getCapacity()`, `getStatus()`, dan untuk fasilitas `getQuantity()`, `getStatus()`, `getEntitySummary()`.

### `rooms.create`
- `$academicUnits` → `AcademicUnit::orderBy('name')->get()`.
- Field (sesuai `StoreRoomRequest`): `academic_unit_id` (wajib, exists), `code` (wajib, maks 30, unik),
  `name` (wajib, maks 150), `building` (wajib, maks 100), `floor` (opsional, maks 30),
  `capacity` (wajib, integer ≥ 1), `status` (wajib, `available|occupied|maintenance`), `description` (opsional).
- Field `type` **tidak diterima** (tidak ada di `StoreRoomRequest` maupun `$fillable`), sehingga ruang baru selalu `classroom`.

### `schedules.index`
- `$schedules` → `Schedule::with('room')->orderBy('start_time')->paginate(10)`.
- `$rooms` → **belum dikirim** oleh `ScheduleController@index`, sehingga form "Tambah Jadwal" (dibungkus `@isset($rooms)`) tidak tampil.
  Route `schedules.store` sendiri sudah berfungsi.
- Field (sesuai `StoreScheduleRequest`): `room_id`, `title` (maks 150), `start_time`, `end_time` (`after:start_time`). Status otomatis `scheduled`.

### `bookings.index`
- `$bookings` → `Booking::with(['room', 'user'])->orderByDesc('start_time')->paginate(10)` (lewat `BookingService::paginateBookings`).

### `bookings.create`
- `$rooms` → `Room::orderBy('name')->get()` (termasuk ruang `maintenance`; pengajuan ke ruang tersebut ditolak Service).
- `$roles` → **tidak dikirim** Controller; view memakai daftar bawaan
  `['Mahasiswa', 'Dosen', 'Admin Akademik', 'Kepala Laboratorium', 'Pimpinan Fakultas']`.
- Field (sesuai `StoreBookingRequest`): `requester_name` (maks 150), `requester_role` (maks 100, teks bebas),
  `room_id` (exists), `participant_count` (integer ≥ 1), `start_time`, `end_time` (`after:start_time`), `purpose` (maks 255).

## 4. Relasi data dan nilai status

| Relasi | Aturan hapus (FK) | Dampak ke UI |
|---|---|---|
| `academic_units` 1–N `rooms` | `restrictOnDelete` | unit tidak bisa dihapus selama punya ruang |
| `rooms` 1–N `facilities` | `cascadeOnDelete` | hapus ruang ikut menghapus fasilitas |
| `rooms` 1–N `schedules` | `cascadeOnDelete` | hapus ruang ikut menghapus jadwal |
| `rooms` 1–N `bookings` | `restrictOnDelete` | **ruang yang punya booking tidak bisa dihapus** |
| `users` 1–N `bookings` | `restrictOnDelete` | – |

Semua kolom booking yang dibutuhkan form sudah ada (`requester_name`, `requester_role`, `participant_count`,
`qr_token`, `checked_in_at`, `checked_out_at`, `rejection_reason`) dan sudah masuk `$fillable`/`casts()`.

| Kolom | Nilai yang valid |
|---|---|
| `rooms.status` | `available`, `occupied`, `maintenance` (nilai lain → exception dari mutator Model) |
| `rooms.type` | `classroom` (default), `laboratory` |
| `facilities.status` | `available`, `damaged`, `maintenance` |
| `schedules.status` | `scheduled` (default); `cancelled` dikecualikan dari perhitungan, belum ada UI-nya |
| `users.role` | `borrower` (default), `lab_head`, `admin`, `leader` |
| `bookings.status` | lihat alur di bawah |

Alur status booking:
```
pending ──approve──> approved ──check-in──> checked_in ──check-out──> completed
   └────reject────> rejected     └── tidak check-in > 15 menit setelah mulai ──> no_show (ghost booking)
```
`cancelled` sudah ada sebagai konstanta di Model, tetapi belum dipakai oleh alur mana pun.
`no_show` ditandai otomatis setiap kali dashboard dibuka (`BookingService::detectGhostBookings`).

## 5. Penanganan pesan sukses dan galat (perilaku aktual)

| Sumber | Bentuk yang diterima view | Tampil di |
|---|---|---|
| Validasi FormRequest gagal | `$errors` per field + `old()` | pesan di bawah field (`@error` / `<x-field-error>`); kotak merah di layout hanya berisi ringkasan jumlah isian, kecuali field yang tidak punya isian di halaman (pesannya didaftar di kotak merah) |
| `BookingException` (ruang maintenance, melebihi kapasitas, bentrok jadwal, status tidak sesuai, bukan approver, token QR salah, di luar jendela check-in) | `session('error')` | **hanya** kotak merah di layout (bukan per field). `store` mempertahankan input lama (`withInput`), aksi lain tidak |
| `authorize()` FormRequest bernilai `false` (belum login) | HTTP **403** | halaman error Laravel, bukan flash |
| Berhasil | `session('success')` | kotak hijau di layout |

> **Catatan `datetime-local`:** input mengirim format `2026-10-21T14:00`. Kolom `start_time`/`end_time` di
> Booking dan Schedule sudah di-cast `datetime`, jadi nilai otomatis dikonversi ke Carbon sebelum query bentrok.
> **Timezone aplikasi masih `UTC`** (`config/app.php`), sehingga jam yang diketik pengguna (WIB) diperlakukan sebagai UTC (lihat bagian 8).

**Pola galat FE-05 (agar satu galat tidak tampil dua kali):**
- Halaman yang menampilkan galat di bawah field mendaftarkan nama field-nya:
  `@section('inline_error_fields', 'room_id,title,start_time,end_time')`. Saat ini dipakai di `rooms/create`,
  `bookings/create`, dan `schedules/index`. Field yang tidak terdaftar (mis. `qr_token`, `rejection_reason` di daftar booking)
  tetap didaftar pesannya di kotak merah layout.
- Pesan per field memakai `<x-field-error for="…" />` (id `{field}-error`). Input yang salah diberi
  `aria-invalid="true"` dan `aria-describedby` yang merujuk id hint/galat.
- Validasi browser memakai atribut HTML5 yang mengikuti FormRequest (`required`, `maxlength`, `min`, `step`, `type`,
  `pattern`). Aturan tambahan di `resources/js/campus-space.js` (form diberi atribut `data-cs-validate`):
  `data-after="id_mulai"` (waktu selesai > mulai), `data-capacity-from="id_select_ruang"` + `data-capacity` pada option
  (peserta ≤ kapasitas), `data-not-blank` (bukan hanya spasi), `data-trim` (rapikan spasi token QR).
  Ini hanya umpan balik awal; validasi utama tetap di server.

## 6. Menambah status/aksi baru (≤ 2 file)

Tombol di kolom **Aksi** dipilih dari file `bookings/partials/actions/{status}.blade.php`.
Contoh menambah tombol *Reject* untuk status `pending`: cukup edit `pending.blade.php` dan tambah route-nya.
Contoh status baru `no_show`: buat `no_show.blade.php`. Status yang tidak punya file akan menampilkan `none.blade.php`.
Warna badge untuk status baru ditambahkan satu selector `[data-status='…' i]` di bagian 5 `resources/css/campus-space.css`
(komponen `components/status-badge.blade.php` tidak perlu diubah).

Kondisi saat ini:
- `pending.blade.php`: tombol Approve + form Reject (`<details>`, field `rejection_reason`, wajib, maks 1000 karakter).
- `approved.blade.php` / `checked_in.blade.php`: input `qr_token` (wajib, maks 64, `pattern="[A-Za-z0-9]+"` karena token
  dibuat server dengan `Str::random`) + tombol simulasi scan check-in / check-out. Status hanya berubah bila server
  menerima token; frontend tidak mengubah status. Saat ini server membalas 403 karena belum ada login (BE-01).
- `rejected.blade.php`: menampilkan `rejection_reason`.
- `completed`, `no_show`, dan `cancelled` jatuh ke `none.blade.php`.
- Badge punya warna untuk `completed` (teal) dan `no_show` (merah); `cancelled` memakai warna default abu-abu.

## 7. Otorisasi (perilaku aktual)

- Aplikasi **belum punya sistem login**, sehingga `$request->user()` selalu `null`.
- `ApproveBookingRequest`, `RejectBookingRequest`, dan `QrScanRequest` mensyaratkan user login. Akibatnya approve, reject,
  check-in, dan check-out **selalu 403** saat ini.
- Approve/reject juga mensyaratkan `users.role` = `lab_head` atau `admin` (`User::canApproveBookings`); selain itu muncul `session('error')`.
- `qr_token` (40 karakter acak) dibuat saat booking disetujui. Check-in hanya diterima mulai 15 menit sebelum `start_time` sampai `end_time`.
- `bookings.requester_role` adalah label teks bebas dari form (Mahasiswa, Dosen, …), **tidak terkait** dengan `users.role`.

## 8. Usulan FE → BE (BELUM DISEPAKATI)

Bagian ini **bukan kontrak**. Nama route dan variabel di bawah hanya usulan agar FE-04 s.d. FE-06 bisa dikerjakan.
Setelah BE menyetujui dan mengimplementasikan, pindahkan butir yang disepakati ke bagian 2–7, lalu hapus dari sini.

| ID | Kebutuhan | Usulan | Memblokir |
|---|---|---|---|
| BE-01 | Cara mendapatkan user ber-role | Login sederhana, atau mekanisme simulasi user khusus lingkungan lokal. Akun dengan role sudah tersedia di seeder branch QA | Approve, Reject, QR (FE-05/06) |
| BE-02 | Sumber `qr_token` untuk simulator | Tentukan bagaimana token sampai ke halaman simulasi, misalnya ditampilkan untuk booking `approved` atau lewat halaman detail booking | QR Interface (FE-05) |
| BE-03 | Data form jadwal | `ScheduleController@index` mengirim `$rooms` (`Room::orderBy('name')->get()`) | Form Tambah Jadwal (FE-05) |
| BE-04 | Tipe ruang | Tambah `type` ke `$fillable` Room dan `StoreRoomRequest` (`Rule::in([...])`), serta daftar tipe yang sah | Field tipe di Room Create/Edit (FE-04/06) |
| BE-05 | CRUD Ruang | `GET /rooms/{room}/edit` (`rooms.edit`, kirim `$room`, `$academicUnits`), `PUT /rooms/{room}` (`rooms.update`, `UpdateRoomRequest` dengan unique `code` mengabaikan ruang itu sendiri; field sama dengan `StoreRoomRequest`), `DELETE /rooms/{room}` (`rooms.destroy`, kembalikan `session('error')` bila ruang masih punya booking). Redirect sukses ke `rooms.index` + `session('success')`. **View `rooms/edit` dan kolom Aksi di `rooms/index` sudah siap** dan aktif otomatis begitu route ini terdaftar | Edit/Delete Room (FE-06) |
| BE-06 | Edit, batal & hapus booking | Aturan usulan: ubah & hapus hanya `pending`; batal untuk `pending`/`approved` → status `cancelled` (konstanta sudah ada). Route: `GET bookings.edit` (kirim `$booking`, `$rooms`, opsional `$roles`), `PUT bookings.update` (`UpdateBookingRequest`, field sama dengan `StoreBookingRequest`, cek ulang kapasitas & bentrok), `bookings.cancel` (PATCH/POST), `DELETE bookings.destroy`. Server wajib menegakkan aturan status. **View `bookings/edit` dan partial `manage` sudah siap** | Edit/Cancel/Delete Booking (FE-06) |
| BE-07 | Timezone | Set timezone aplikasi ke `Asia/Jakarta` agar input `datetime-local`, jendela check-in, dan jam operasional 07.00–17.00 konsisten | Booking, QR, dashboard |
| BE-08 | Galat per field (opsional) | Galat kapasitas ke `participant_count` dan bentrok ke `start_time` lewat `withErrors`, agar tampil di bawah field | Form Validation (FE-05) |
| BE-09 | Ringkasan dashboard | Pindahkan rata-rata utilisasi dan total ghost ke `summary`, serta tambahkan jumlah ruang `maintenance` | Dashboard dan laporan (FE-04/06) |
| BE-10 | Data demo | Merge seeder dari branch `feature/PAD-21-qa-seeder-strategy-test` dan putuskan apakah tipe `auditorium` ikut di-merge | Pengujian semua halaman |
| BE-11 | Laporan utilisasi | Putuskan apakah laporan = data dashboard yang sama + tampilan cetak (tanpa logika baru), atau route terpisah. **Saat ini FE memakai dashboard + cetak** | Utilization Report (FE-06) |
| BE-12 | Metrik konflik jadwal | Catat jumlah pengajuan yang ditolak karena bentrok (per ruang & periode), lalu kirim di data dashboard, mis. `rooms[].conflict_rejections` dan `summary.conflict_rejections`. Tanpa data ini laporan menampilkan "Tidak tersedia" | Metrik & rekomendasi konflik (FE-06) |
| BE-13 | Jumlah booking per ruang | `RoomController@index` memakai `Room::withCount('bookings')` agar view bisa menyembunyikan tombol Hapus untuk ruang yang masih punya booking (FK `restrictOnDelete`) | Delete Room (FE-06) |
