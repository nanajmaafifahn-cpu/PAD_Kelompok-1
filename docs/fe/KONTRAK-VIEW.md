# Kontrak View ↔ Controller

Dokumen ini untuk anggota **Back-End** dan **Front-End**. Semua view Blade hanya *menampilkan* data; seluruh query,
rumus, dan aturan bisnis ada di Controller/Service/Model (termasuk scope). Bila view butuh data baru, tambahkan di
Controller/Service lalu catat di dokumen ini; **jangan menulis query di Blade**.

> **Diverifikasi terhadap kode:** branch `feature/be-perbaikan-inti` (PROMPT 1 Fase 0–3 + backend tambahan untuk UI, 8 Nov 2026).
> Bagian 1–7 = kontrak yang **sudah berlaku di kode**. Bagian 8 = status usulan lama BE-01 s.d. BE-13 dan pekerjaan lanjutan.

## 1. Daftar file view

| File | Dipanggil oleh |
|---|---|
| `layouts/app.blade.php` | semua halaman aplikasi (wajib login) |
| `layouts/guest.blade.php` | `auth/login`, `auth/register` |
| `layouts/partials/flash.blade.php` | layout (pesan sukses/galat) |
| `components/nav-link.blade.php` | menu; hanya tampil bila route-nya ada (`Route::has`) |
| `components/status-badge.blade.php` | badge status (warna via CSS `data-status`) |
| `components/progress-bar.blade.php`, `components/field-error.blade.php`, `components/route-form.blade.php` | komponen umum |
| `components/icon`, `sidebar-link`, `page-header`, `stat-card`, `empty-state`, `room-card`, `confirm-dialog`, `filter-bar` | komponen UI PROMPT 2 (lihat bagian 9) |
| `vendor/pagination/tailwind.blade.php` | paginasi bergaya Campus Space untuk semua `$paginator->links()` |
| `auth/login.blade.php`, `auth/register.blade.php` | `LoginController@create`, `RegisterController@create` |
| `dashboard/borrower.blade.php` | `DashboardController` (role `borrower`) |
| `dashboard/manager.blade.php` | `DashboardController` (role `lab_head`, `admin`, `leader`) |
| `dashboard/utilization.blade.php` | `UtilizationDashboardController@index` (Laporan Utilisasi) |
| `rooms/index.blade.php` | `RoomController@index` (Jelajahi Ruangan) |
| `rooms/show.blade.php` | `RoomController@show` (Detail Ruangan) |
| `rooms/manage.blade.php` | `RoomController@manage` (Ruangan & Foto) |
| `rooms/create.blade.php`, `rooms/edit.blade.php`, `rooms/partials/form.blade.php` | `RoomController@create/edit` |
| `rooms/qr.blade.php` | `RoomController@qrIndex` (Cetak QR Ruangan) |
| `facilities/index.blade.php`, `facilities/edit.blade.php`, `facilities/partials/form.blade.php` | `FacilityController` |
| `schedules/index.blade.php`, `schedules/edit.blade.php`, `schedules/partials/form.blade.php` | `ScheduleController` |
| `bookings/index.blade.php` | `BookingController@index` (semua booking) dan `@mine` (Peminjaman Saya) |
| `bookings/show.blade.php` | `BookingController@show` |
| `bookings/create.blade.php`, `bookings/edit.blade.php`, `bookings/partials/form.blade.php` | `BookingController@create/edit` |
| `bookings/partials/actions/{status}.blade.php` | dipilih otomatis sesuai status (`pending`, `approved`, `checked_in`, `rejected`, `none`) |
| `bookings/partials/manage.blade.php` | tombol Ubah / Batalkan / Hapus (tampil bila route ada **dan** `@can`) |
| `scan/index.blade.php`, `scan/show.blade.php` | `ScanController@index/show` |
| `admin/units/*`, `admin/users/*` | `Admin\AcademicUnitController`, `Admin\UserController` |

## 2. Route yang tersedia (`routes/web.php`)

Semua route selain login/register memakai middleware `auth` + `active` (akun nonaktif dikeluarkan).
Kolom **Middleware** hanya menulis tambahan di luar itu. Policy tetap dicek ulang di FormRequest/Controller.

| Nama route | Method & URI | Controller | Middleware tambahan |
|---|---|---|---|
| `login`, `login.store` | `GET/POST /login` | `Auth\LoginController@create/store` | `guest`; store: `throttle:login` (5/menit per email+IP) |
| `register`, `register.store` | `GET/POST /register` | `Auth\RegisterController@create/store` | `guest` (selalu role `borrower`) |
| `logout` | `POST /logout` | `Auth\LoginController@destroy` | – |
| `home` | `GET /` | `HomeController` (redirect ke `dashboard`) | – |
| `dashboard` | `GET /dashboard` | `DashboardController` | – (isi sesuai role) |
| `dashboard.utilization` | `GET /dashboard/utilization` | `UtilizationDashboardController@index` | `role:lab_head,admin,leader` |
| `rooms.index` | `GET /rooms` | `RoomController@index` | – |
| `rooms.show` | `GET /rooms/{room}` | `RoomController@show` | – |
| `rooms.manage` | `GET /rooms/manage` | `RoomController@manage` | `role:lab_head,admin` |
| `rooms.create`, `rooms.store` | `GET /rooms/create`, `POST /rooms` | `RoomController` | `role:lab_head,admin` |
| `rooms.edit`, `rooms.update`, `rooms.destroy` | `GET /rooms/{room}/edit`, `PUT/DELETE /rooms/{room}` | `RoomController` | `role:lab_head,admin` |
| `rooms.qr.index` | `GET /rooms/qr-codes` | `RoomController@qrIndex` | `role:lab_head,admin` |
| `rooms.qr.regenerate` | `POST /rooms/{room}/qr-token` | `RoomController@regenerateQr` | `role:lab_head,admin` |
| `rooms.facilities.index`, `rooms.facilities.store` | `GET/POST /rooms/{room}/facilities` | `FacilityController` | `role:lab_head,admin` |
| `facilities.edit`, `facilities.update`, `facilities.destroy` | `GET /facilities/{facility}/edit`, `PUT/DELETE /facilities/{facility}` | `FacilityController` | `role:lab_head,admin` |
| `schedules.index` | `GET /schedules` | `ScheduleController@index` | – |
| `schedules.store`, `schedules.edit`, `schedules.update`, `schedules.cancel`, `schedules.destroy` | `POST /schedules`, `GET /schedules/{schedule}/edit`, `PUT /schedules/{schedule}`, `PATCH /schedules/{schedule}/cancel`, `DELETE /schedules/{schedule}` | `ScheduleController` | `role:lab_head,admin` |
| `bookings.index` | `GET /bookings` | `BookingController@index` | – (peminjam diarahkan ke `bookings.mine`) |
| `bookings.mine` | `GET /bookings/mine` | `BookingController@mine` | – |
| `bookings.create`, `bookings.store` | `GET /bookings/create`, `POST /bookings` | `BookingController` | `role:borrower,lab_head,admin`; store: `throttle:booking-store` (10/menit) |
| `bookings.show` | `GET /bookings/{booking}` | `BookingController@show` | – (Policy `view`: pemilik atau pengelola/admin/pimpinan) |
| `bookings.edit`, `bookings.update` | `GET /bookings/{booking}/edit`, `PUT /bookings/{booking}` | `BookingController` | update: `throttle:booking-store` (Policy: pemilik, status pending) |
| `bookings.cancel` | `PATCH /bookings/{booking}/cancel` | `BookingController@cancel` | – (pemilik; pending/approved yang belum mulai) |
| `bookings.destroy` | `DELETE /bookings/{booking}` | `BookingController@destroy` | `role:lab_head,admin` (hanya pending/rejected/cancelled) |
| `bookings.approve` | `POST\|PATCH /bookings/{booking}/approve` | `BookingController@approve` | `role:lab_head,admin` |
| `bookings.reject` | `POST /bookings/{booking}/reject` | `BookingController@reject` | `role:lab_head,admin` (field `rejection_reason`) |
| `bookings.checkin`, `bookings.checkout` | `POST\|PATCH /bookings/{booking}/check-in\|check-out` | `BookingController` | `throttle:qr-scan` (kode cadangan, hanya pemilik) |
| `scan.index` | `GET /scan` | `ScanController@index` | – (role borrower/lab_head/admin) |
| `scan.manual` | `POST /scan` | `ScanController@manual` | `throttle:qr-scan` (field `code`) |
| `scan.room`, `scan.confirm` | `GET/POST /scan/{room:qr_token}` | `ScanController@show/confirm` | `throttle:qr-scan` |
| `admin.units.*` | `GET/POST /admin/units`, `GET /admin/units/{unit}/edit`, `PUT/DELETE /admin/units/{unit}` | `Admin\AcademicUnitController` | `role:admin` |
| `admin.users.*` | `GET/POST /admin/users`, `GET /admin/users/create`, `GET /admin/users/{user}/edit`, `PUT /admin/users/{user}` | `Admin\UserController` | `role:admin` |

- Form `PUT/PATCH/DELETE` memakai `@method(...)`; komponen `<x-route-form route="..." :params="...">` mengambil method dari definisi route.
- `bookings.reject` **hanya POST**.
- **Belum ada route**: aturan operasional/hari libur, export CSV, notifikasi, filter jadwal & booking, halaman panduan.

## 3. Variabel per view

### `dashboard.borrower` (route `dashboard`, role borrower) — `DashboardService::borrower()`
- `$stats` → `['total', 'pending', 'active' (approved + checked_in), 'completed']`
- `$recentBookings` → 5 booking milik user terbaru (relasi `room`)
- `$nextBooking` → booking `approved`/`checked_in` terdekat yang belum selesai, atau `null` (relasi `room`)
- `$nextBookingScannable` → `bool`, tampilkan tombol **Pindai QR** (sedang check-in atau dalam jendela check-in)

### `dashboard.manager` (route `dashboard`, role lab_head/admin/leader) — `DashboardService::manager()`
- `$stats` → `['rooms_active' (selain maintenance), 'available_now', 'occupied_now', 'pending']`
- `$pendingBookings` → 5 booking `pending` dengan waktu mulai terdekat (relasi `room`, `user`)
- `$rooms` → semua `Room` urut kode; `$liveStatuses` → `[room_id => status live]`
- Tombol Setujui/Tolak memakai partial `actions/pending` (ber-`@can`); pimpinan hanya membaca.

### `dashboard.utilization` (Laporan Utilisasi)
Data `UtilizationDashboardService::build()`; header `Accept: application/json` mengembalikan data yang sama (khusus pengelola/admin/pimpinan).
Query string opsional `start`, `end` (tanggal). Tanpa keduanya: minggu berjalan, jam yang belum terjadi tidak dihitung.

```php
[
    'period' => ['start' => 'Y-m-d', 'end' => 'Y-m-d', 'available_hours' => float],
    'summary' => [
        'total_rooms' => int, 'underutilized' => int, 'overutilized' => int,
        'occupied_now' => int, 'booked_now' => int, 'available_now' => int, 'maintenance_now' => int,
        'total_scheduled_hours' => float, 'total_actual_hours' => float, 'total_ghost_bookings' => int,
        'average_utilization_rate' => float, 'average_scheduled_rate' => float, // persen, 1 desimal
        'open_sessions' => int, // booking checked_in di periode yang belum check-out
    ],
    'rooms' => [[ 'room_id', 'room_code', 'room_name', 'room_type', 'available_hours', 'scheduled_hours',
                  'actual_hours', 'scheduled_rate', 'utilization_rate', 'approved_bookings', 'ghost_bookings',
                  'ghost_rate', 'category' => 'underutilized|normal|overutilized', 'metrics' => [...],
                  'live_status' => 'available|booked|occupied|maintenance', 'academic_unit' => ?string ]],
    'underutilized' => ['KODE', ...], 'overutilized' => ['KODE', ...],
]
```
- `metrics` classroom: `realization_ratio`, `seat_occupancy_ratio`; laboratory: `setup_hours`, `ghost_penalty_hours`, `raw_actual_rate`.
- View lama masih menghitung rata-rata di `@php`; ganti dengan `summary.average_*` saat UI dirombak.

### `rooms.index` (Jelajahi Ruangan)
- `$rooms` → paginator 10 (relasi `academicUnit`, `facilities`; `bookings_count`), `withQueryString()`
- `$liveStatuses` → `[room_id => available|booked|occupied|maintenance]` (`RoomStatusService`, 2 query total)
- `$filters` → `['q', 'type', 'min_capacity', 'facility']`; `$types` → `Room::TYPE_LABELS`; `$facilityNames` → nama fasilitas unik
- Per ruang: `$room->typeLabel()`, `$room->photoUrl()` (atau `null`), `$room->computerCount()` (0 = nonkomputer; memakai `facilities` yang sudah dimuat)

### `rooms.show` (Detail Ruangan)
- `$room` (relasi `academicUnit`, `facilities`), `$liveStatus`, `$computerCount`
- `$upcomingEvents` → maks 5 kegiatan terdekat yang belum selesai (`CalendarService::upcomingForRoom`), bentuk kegiatan lihat `schedules.index`
- Tombol **Ajukan Peminjaman** → `route('bookings.create', ['room_id' => $room->id])`

### `rooms.manage` (Ruangan & Foto)
- `$rooms` → paginator 15 urut kode (relasi `academicUnit`, `facilities`; `bookings_count`); `$liveStatuses`

### `rooms.create` / `rooms.edit`
- `$academicUnits`, `$types` (`Room::TYPE_LABELS`); edit: `$room`
- Field: `academic_unit_id`, `code` (unik), `type` (`laboratory|classroom`), `name`, `building`, `floor`, `capacity` (≥ 1),
  `status` (`available|maintenance`), `description`, `photo` (jpg/png/webp ≤ 2 MB), edit: `remove_photo`.
  Form wajib `enctype="multipart/form-data"`. Foto tampil lewat `/storage` → jalankan `php artisan storage:link`.

### `rooms.qr`
- `$rooms` (urut kode), `$baseUrl` (`APP_URL`). Isi QR = alamat dasar + `route('scan.room', $room, false)`; gambar dibuat `resources/js/qr.js`.

### `facilities.index` / `facilities.edit`
- `$room`, `$facilities`, `$statuses` (`Facility::STATUS_LABELS`: available=Baik, damaged=Rusak, maintenance=Perawatan); edit: `$facility`
- Field: `name` (unik per ruang), `quantity` (≥ 0), `status`

### `schedules.index`
- `$schedules` → paginator 10 urut waktu mulai (relasi `room`) — tabel kelola untuk pengelola
- `$rooms` → untuk form tambah jadwal
- `$week` → `CalendarService::week()` minggu berjalan:
  `['start' => Carbon, 'end' => Carbon, 'total' => int, 'days' => [7 × ['date' => Carbon, 'is_today' => bool, 'events' => Collection]]]`
- Satu kegiatan: `['type' => 'schedule'|'booking', 'title', 'room_id', 'room_name', 'room_code', 'start' => Carbon, 'end' => Carbon, 'status']`
  (jadwal `cancelled` dan booking selain approved/checked_in/completed tidak ikut)
- `$eventTypes` → `CalendarService::TYPE_LABELS` (`schedule` = Jadwal kuliah, `booking` = Peminjaman disetujui) untuk legenda
- Field jadwal: `room_id`, `title`, `start_time`, `end_time`; bentrok ditolak di field `start_time`.

### `bookings.index` (`$scope = 'all'`) / `bookings.mine` (`$scope = 'mine'`)
- `$bookings` → paginator 10 urut waktu mulai terbaru (relasi `room`, `user`)

### `bookings.show`
- `$booking` (relasi `room.academicUnit`, `user`), `$isOwner` (bool), `$backupCode` (kode cadangan; hanya pemilik saat approved/checked_in, selain itu `null`)

### `bookings.create` / `bookings.edit`
- `$rooms`; create: `$selectedRoomId` (dari `?room_id=`, `null` bila tidak valid/maintenance); edit: `$booking`
- Field: `room_id`, `participant_count`, `start_time` (`after:now`), `end_time`, `purpose`. Nama & peran pemohon diambil dari akun (bukan input).

### `scan.index` / `scan.room`
- `scan.index`: tanpa variabel. `scan.room`: `$room`, `$result` (`App\Support\Scan\ScanResult`: `action` = `check_in|check_out|null`, `booking`, `message`).

### Admin
- `admin.units.index`: `$units` (dengan `rooms_count`); `admin.units.edit`: `$unit`
- `admin.users.index`: `$users` (paginator 15), `$roles`; `create`: `$roles`; `edit`: `$user`, `$roles`, `$isSelf`

## 4. Relasi data dan nilai status

| Relasi | Aturan hapus (FK) | Dampak ke UI |
|---|---|---|
| `academic_units` 1–N `rooms` | `restrictOnDelete` | unit yang punya ruang tidak bisa dihapus |
| `rooms` 1–N `facilities` / `schedules` | `cascadeOnDelete` | ikut terhapus bersama ruang |
| `rooms` 1–N `bookings` | `restrictOnDelete` | ruang yang punya booking (aktif atau riwayat) tidak bisa dihapus; sarankan status Maintenance |
| `users` 1–N `bookings` | `restrictOnDelete` | akun tidak dihapus, cukup dinonaktifkan |

| Kolom | Nilai valid | Label UI (satu sumber) |
|---|---|---|
| `rooms.status` | `available`, `maintenance` | — |
| status live ruang (dihitung) | `available`, `booked`, `occupied`, `maintenance` | `Room::LIVE_STATUS_LABELS` |
| `rooms.type` | `laboratory`, `classroom` (kunci Strategy) | `Room::TYPE_LABELS` (Computer Lab, Class Lab) |
| `rooms.qr_token` | unik, diisi otomatis | — |
| `facilities.status` | `available`, `damaged`, `maintenance` | `Facility::STATUS_LABELS` |
| `schedules.status` | `scheduled`, `cancelled` | — |
| `users.role` | `borrower`, `lab_head`, `admin`, `leader` | `User::ROLE_LABELS` |
| `users.is_active` | `true`/`false` | — |

Alur status booking:
```
pending ──approve──> approved ──check-in──> checked_in ──check-out──> completed
   │                    │   └── tidak check-in s.d. 15 menit setelah mulai ──> no_show (ghost)
   ├──reject──> rejected└── batal (pemilik, sebelum mulai) ──> cancelled
   └── batal (pemilik) ──> cancelled
```
Jendela check-in: 15 menit sebelum s.d. 15 menit setelah mulai (`Booking::CHECK_IN_OPENS_BEFORE_MINUTES`, `GHOST_TOLERANCE_MINUTES`).
`no_show` ditandai saat Laporan Utilisasi dibuka (`BookingService::detectGhostBookings`); scheduler belum ada.

## 5. Penanganan pesan sukses dan galat

| Sumber | Bentuk | Tampil di |
|---|---|---|
| Validasi FormRequest | `$errors` per field + `old()` | `<x-field-error for="…">` di bawah field |
| `BookingException` dengan field (kapasitas → `participant_count`, bentrok/waktu lampau → `start_time`, maintenance → `room_id`) | `$errors` per field | di bawah field terkait |
| `BookingException` tanpa field (status tidak sesuai, bukan pemilik, dll.) | `$errors['booking']` (form) atau `session('error')` (aksi) | kotak galat layout |
| Throttle | `$errors['throttle']` | kotak galat layout |
| Policy/role ditolak | HTTP 403 | halaman error |
| Berhasil | `session('success')` | kotak hijau layout |

- Halaman dengan galat per field mendaftarkan fieldnya: `@section('inline_error_fields', 'room_id,start_time,…')` agar tidak tampil dua kali.
- Timezone aplikasi `Asia/Jakarta` (`APP_TIMEZONE`); input `datetime-local` dianggap WIB.
- Validasi klien (`data-cs-validate` di `campus-space.js`): `data-after`, `data-capacity-from`, `data-not-blank`, `data-trim`. Validasi utama tetap di server.

## 6. Menambah status/aksi baru (≤ 2 file)

Tombol aksi booking dipilih dari `bookings/partials/actions/{status}.blade.php`; status tanpa file memakai `none.blade.php`.
Warna badge baru cukup satu selector `[data-status='…' i]` di `resources/css/campus-space.css`.
Setiap tombol aksi wajib dibungkus `@can(...)` sesuai `BookingPolicy` (`approve`, `reject`, `checkIn`, `checkOut`, `update`, `cancel`, `delete`).

## 7. Otorisasi

| Role | Label | Akses |
|---|---|---|
| `borrower` | Mahasiswa / Dosen | dashboard peminjam, jelajahi & detail ruang, jadwal, ajukan/ubah/batal booking miliknya, Peminjaman Saya, scan QR |
| `lab_head` | Pengelola Ruang / Kepala Lab | semua di atas + dashboard pengelola, laporan, approve/reject/hapus booking, kelola ruang/foto/fasilitas/jadwal, cetak QR |
| `admin` | Admin Akademik | semua akses `lab_head` + kelola unit akademik & pengguna (tidak bisa menurunkan role/menonaktifkan diri sendiri) |
| `leader` | Pimpinan | hanya membaca: dashboard pengelola, laporan, daftar ruang/jadwal/booking; tidak bisa mengajukan atau mengubah |

- Middleware alias: `role:...` (`EnsureUserHasRole`), `active` (`EnsureUserIsActive`). Policy: `BookingPolicy`, `RoomPolicy`, `SchedulePolicy`, `FacilityPolicy`, `AcademicUnitPolicy`, `UserPolicy`; Gate `view-utilization-data`.
- QR: setiap ruang punya `qr_token`. Scan hanya memproses booking milik user login. Kode cadangan (`bookings.qr_token`) hanya ditampilkan ke pemilik.

## 8. Status usulan lama & pekerjaan lanjutan

| ID | Status |
|---|---|
| BE-01 login/role | Selesai (Fase 1) |
| BE-02 sumber `qr_token` | Selesai: QR per ruangan + kode cadangan di detail booking (Fase 2) |
| BE-03 `$rooms` di jadwal | Selesai (Fase 3) |
| BE-04 tipe ruang | Selesai (Fase 3) |
| BE-05 CRUD ruang | Selesai; redirect sukses ke `rooms.manage` |
| BE-06 edit/batal/hapus booking | Selesai dengan nama route di atas |
| BE-07 timezone | Selesai (Fase 1) |
| BE-08 galat per field | Selesai (Fase 3) |
| BE-09 ringkasan dashboard | Selesai (`summary.*` di atas) |
| BE-10 data demo | Selesai: seeder QA + `DemoCampusSeeder` (14 ruang TULT + data minggu berjalan) |
| BE-11 laporan | Selesai: `dashboard.utilization` = Laporan Utilisasi, `dashboard` = dashboard per role |
| BE-12 metrik konflik jadwal | **Belum** |
| BE-13 `bookings_count` | Selesai |

Belum dikerjakan (jangan buat tombol/menu palsu): aturan operasional & hari libur, scheduler ghost/auto check-out,
export CSV, notifikasi, filter jadwal (tanggal/ruang, minggu sebelumnya/berikutnya) dan filter booking, halaman panduan,
fakultas pada akun user (topbar memakai teks tetap "Fakultas Rekayasa Industri"), waktu persetujuan booking (`approved_at`).

## 9. Komponen & pola UI (PROMPT 2, layout sidebar)

| Komponen / pola | Pakai untuk | Catatan |
|---|---|---|
| `@section('page_header')` + `<x-page-header eyebrow title subtitle crumb>` | header setiap halaman (breadcrumb "Campus Space / …", eyebrow, judul, subjudul, slot `actions`) | layout menaruh pesan flash tepat di bawahnya |
| `<x-icon name="…">` | ikon SVG garis (home, search, calendar, clipboard, qr, chart, building, monitor, printer, users, academic, clock, check-circle, activity, door, alert, …) | dekoratif (`aria-hidden`); tombol ikon wajib `aria-label` |
| `<x-sidebar-link route icon active>` | menu sidebar | tampil hanya bila `Route::has()`; menu per role di `layouts/app.blade.php` |
| `<x-stat-card label value caption icon tone>` | kartu statistik di dalam `<dl>` | `tone` = nama status (`pending`, `completed`, `occupied`, …) untuk warna chip |
| `<x-empty-state title text>` + slot tombol | keadaan kosong | — |
| `<x-status-badge :status domain>` | badge status berbahasa Indonesia | label dari `Booking::STATUS_LABELS`, `Room::LIVE_STATUS_SHORT_LABELS`, `Schedule::STATUS_LABELS`, `Facility::STATUS_LABELS`, `UtilizationResult::CATEGORY_LABELS`; kondisi fasilitas wajib `domain="facility"` |
| `<x-room-card :room :live-status>` | grid Jelajahi Ruangan | butuh relasi `facilities` sudah dimuat (jumlah komputer) |
| `<form data-confirm="pesan">` / `<x-route-form confirm="…">` | konfirmasi hapus/batal/setujui | memakai `<dialog id="cs-confirm-dialog">` di layout (bukan `confirm()`) |
| `<x-confirm-dialog id title :action reason>` + `<button data-dialog-open="id" hidden>` | dialog berform sendiri, mis. Tolak + alasan wajib | sediakan fallback `<noscript>` bila perlu |
| `<x-filter-bar :action :reset :active :cols>` | filter GET (Jelajahi Ruangan, Laporan) | nilai field diisi dari variabel controller |
| `App\Support\DateFormat` | **satu-satunya** tempat format tanggal/jam di view | `date`, `longDate`, `time`, `dateTime` ("8 Nov 2026, 10.36 WIB"), `range`, `weekRange`, `dayName`, `monthYear`, `inputValue` (datetime-local), `timezoneLabel` |
| `Booking::timelineSteps()` | timeline detail peminjaman | state `done/current/upcoming/failed` |
| Tabel responsif | semua tabel data | `cs-table-wrap cs-table-wrap-stack` + `cs-table cs-table-stack` + `data-label` di setiap `<td>`; di bawah 768px menjadi kartu |
| Tombol submit | semua form POST | otomatis nonaktif + "Menyimpan..." (teks lain: `data-loading-text`) |
| Cetak | Laporan (A4 lanskap), Cetak QR (`@page qr-cards`, A4 potret, 2 kartu/halaman, QR 6,5 cm) | sidebar/topbar/tombol (`.cs-no-print`) tidak dicetak |

Warna grafik Laporan Utilisasi: terjadwal `#2a78d6`, aktual `#1baf7a` (divalidasi untuk buta warna; label persen selalu tampil).
Teal merek `#0A7E78` (bukan `#0E9F97` dari prototipe) agar teks putih ≥ 4.5:1.

### Kebutuhan backend untuk sesi berikutnya

| Kebutuhan | Dipakai di | Usulan |
|---|---|---|
| Unit akademik pada akun user (`users.academic_unit_id`) | topbar (nama fakultas) | sekarang teks tetap "Fakultas Rekayasa Industri" |
| Halaman panduan (`help` / `guide`) | tombol "?" topbar, link "Buka panduan" sidebar | disembunyikan sampai route ada |
| Filter jadwal: `?date=`, `?room_id=` + minggu sebelumnya/berikutnya di `ScheduleController@index` (`CalendarService::week($date)`) | Jadwal Ruangan, tombol "Buka Kalender" per ruang | kalender saat ini hanya minggu berjalan |
| Filter status peminjaman (`?status=`) di `bookings.index` / `bookings.mine` | Kelola Peminjaman, Peminjaman Saya | dropdown status belum ditampilkan |
| Daftar fasilitas semua ruang (`facilities.index`) | menu "Fasilitas" | menu sementara menuju Ruangan & Foto |
| Waktu keputusan (`approved_at`, `rejected_at`) | timeline detail peminjaman | langkah "Disetujui" belum punya jam |
| Jenis akses ruang (mis. smartdoor) | detail ruang | diganti "Lokasi" |
| Endpoint JSON evaluasi scan | panel kanan Pindai QR tanpa pindah halaman | sekarang membuka halaman `scan.room` |
| Export CSV, notifikasi, aturan operasional | Laporan, topbar, menu Pengelolaan | belum ada (tidak ditampilkan) |
