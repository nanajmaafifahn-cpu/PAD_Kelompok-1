# Role Pengguna — Campus Space

Dokumen acuan pembagian peran (role) dan hak akses. Isinya diambil dari kode, jadi bila kode berubah, dokumen ini
ikut diperbarui:

- Daftar role: `app/Models/User.php` (`ROLE_BORROWER`, `ROLE_LAB_HEAD`, `ROLE_ADMIN`, `ROLE_LEADER`)
- Hak akses: `app/Policies/*Policy.php` dan middleware `role:` di `routes/web.php`
- Menu per role: `resources/views/layouts/app.blade.php`

## 1. Ringkasan 4 role

| Role (kode) | Label di aplikasi | Siapa | Tugas utama |
|---|---|---|---|
| `borrower` | Mahasiswa / Dosen | Mahasiswa, dosen, organisasi kemahasiswaan | Meminjam ruang dan mencatat pemakaian lewat QR |
| `lab_head` | Pengelola Ruang / Kepala Lab | Laboran, kepala laboratorium | Menyetujui peminjaman dan mengelola data ruang |
| `admin` | Admin Akademik | Staf administrasi fakultas | Semua tugas pengelola + mengelola akun dan unit akademik |
| `leader` | Pimpinan | Pimpinan fakultas (mis. Wakil Dekan II) | Memantau utilisasi ruang untuk mengambil keputusan |

Akun yang mendaftar sendiri lewat halaman **Daftar Akun** selalu menjadi `borrower`. Role lain hanya bisa
diberikan oleh Admin di menu **Pengguna**.

## 2. Hak akses per fitur

✅ = boleh · ❌ = tidak boleh · "milik sendiri" = hanya data yang dibuat akun tersebut

| Fitur | Borrower | Lab Head | Admin | Pimpinan |
|---|:---:|:---:|:---:|:---:|
| Dashboard | ✅ (dashboard peminjam) | ✅ (dashboard pengelola) | ✅ (dashboard pengelola) | ✅ (dashboard pengelola) |
| Jelajahi ruangan & detail ruangan | ✅ | ✅ | ✅ | ❌ (tidak ada di menu) |
| Lihat jadwal ruangan | ✅ | ✅ | ✅ | ❌ (tidak ada di menu) |
| Ajukan peminjaman | ✅ | ✅ | ✅ | ❌ |
| Ubah / batalkan peminjaman | milik sendiri | milik sendiri | milik sendiri | ❌ |
| Check-in / check-out (pindai QR) | milik sendiri | milik sendiri | milik sendiri | ❌ |
| Lihat semua peminjaman | ❌ (hanya milik sendiri) | ✅ | ✅ | ✅ (baca saja, lewat dashboard) |
| Setujui / tolak peminjaman | ❌ | ✅ | ✅ | ❌ |
| Hapus data peminjaman | ❌ | ✅ | ✅ | ❌ |
| Laporan Utilisasi | ❌ | ✅ | ✅ | ✅ |
| Kelola ruangan & foto | ❌ | ✅ | ✅ | ❌ |
| Kelola fasilitas ruang | ❌ | ✅ | ✅ | ❌ |
| Kelola jadwal kuliah | ❌ | ✅ | ✅ | ❌ |
| Cetak / perbarui QR ruangan | ❌ | ✅ | ✅ | ❌ |
| Kelola pengguna (tambah, ubah role, nonaktifkan) | ❌ | ❌ | ✅ | ❌ |
| Kelola unit akademik | ❌ | ❌ | ✅ | ❌ |

Catatan:

- Admin **tidak bisa** menurunkan role atau menonaktifkan akunnya sendiri, supaya sistem tidak kehilangan admin.
- Akun yang dinonaktifkan tidak bisa login, tetapi riwayat peminjamannya tetap tersimpan untuk laporan.
- Lab Head dan Admin boleh mengajukan peminjaman (lewat tombol **Ajukan Peminjaman** di Detail Ruangan), tetapi
  menu **Peminjaman Saya** dan **Pindai QR** hanya tampil di sidebar Borrower.

## 3. Menu sidebar per role

| Borrower | Lab Head | Admin | Pimpinan |
|---|---|---|---|
| Dashboard | Dashboard | Dashboard | Dashboard |
| Jelajahi Ruangan | Jelajahi Ruangan | Jelajahi Ruangan | Laporan Utilisasi |
| Jadwal Ruangan | Jadwal Ruangan | Jadwal Ruangan | |
| Peminjaman Saya | Peminjaman | Peminjaman | |
| Pindai QR | Laporan Utilisasi | Laporan Utilisasi | |
| | **Pengelolaan:** Ruangan & Foto, Fasilitas, Cetak QR Ruangan | **Pengelolaan:** Ruangan & Foto, Fasilitas, Cetak QR Ruangan, Pengguna, Unit Akademik | |

## 4. Akun demo

Semua akun memakai kata sandi `password` (hanya untuk pengembangan lokal).

| Email | Role |
|---|---|
| `mahasiswa@campus.test` | Borrower (mahasiswa) |
| `dosen@campus.test` | Borrower (dosen) |
| `pengelola@campus.test` | Lab Head |
| `admin@campus.test` | Admin |
| `pimpinan@campus.test` | Pimpinan |
