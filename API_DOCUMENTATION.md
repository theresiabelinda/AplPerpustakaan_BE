# Dokumentasi API - Sistem Perpustakaan Backend

Dokumen ini menjelaskan semua *endpoint* API yang tersedia untuk aplikasi perpustakaan.

**Base URL:** `/api`
**Otentikasi:** Menggunakan **Laravel Sanctum Token**. Token harus dikirimkan di *header* `Authorization: Bearer <token>` untuk semua *endpoint* yang membutuhkan login.

---

## 1. Otentikasi (Public)

| Method | Endpoint | Deskripsi | Middleware |
| :--- | :--- | :--- | :--- |
| **POST** | `/login` | Otentikasi pengguna dan mengembalikan *token* Sanctum. | None |
| **POST** | `/logout` | Membatalkan *token* pengguna saat ini. | `auth:sanctum` |

### POST /api/login

| Parameter | Tipe | Wajib? | Deskripsi |
| :--- | :--- | :--- | :--- |
| `email` | string | Ya | Email pengguna (contoh: `admin@perpus.id`) |
| `password` | string | Ya | Kata sandi pengguna |

**Response Sukses (200):**
```json
{
  "message": "Login berhasil.",
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "user": {
    "id": 1,
    "name": "Admin Perpustakaan",
    "email": "admin@perpus.id",
    "role": "admin"
  }
  }
  ```


## 2. Manajemen Buku (Admin Only)

**Middleware:** `auth:sanctum`, `role:admin`

| Method | Endpoint | Deskripsi |
| :--- | :--- | :--- |
| **GET** | `/admin/books` | Mendapatkan daftar semua buku. Mendukung *query* `?search=` dan `?page=`. |
| **POST** | `/admin/books` | Menambah buku baru. |
| **GET** | `/admin/books/{id}` | Melihat detail buku. |
| **PUT** | `/admin/books/{id}` | Mengubah data buku. |
| **DELETE** | `/admin/books/{id}` | Menghapus buku. |

### POST /api/admin/books

| Parameter | Tipe | Wajib? | Deskripsi |
| :--- | :--- | :--- | :--- |
| `title` | string | Ya | Judul buku |
| `author` | string | Ya | Nama penulis |
| `publisher` | string | Ya | Penerbit |
| `year` | integer | Ya | Tahun terbit (min 1900, max tahun sekarang) |
| `stock` | integer | Ya | Jumlah stok buku yang tersedia |

---

## 3. Manajemen Anggota (Admin Only)

**Middleware:** `auth:sanctum`, `role:admin`

| Method | Endpoint | Deskripsi |
| :--- | :--- | :--- |
| **GET** | `/admin/users` | Mendapatkan daftar semua anggota. Mendukung *query* `?search=` dan `?status=`. |
| **POST** | `/admin/users` | Menambah anggota baru. |
| **GET** | `/admin/users/{id}` | Melihat detail anggota (termasuk riwayat pinjaman). |
| **PUT** | `/admin/users/{id}` | Mengubah data anggota. |
| **DELETE** | `/admin/users/{id}` | Menghapus anggota. |

---

## 4. Transaksi Peminjaman & Denda (Admin Only)

**Middleware:** `auth:sanctum`, `role:admin`

| Method | Endpoint | Deskripsi |
| :--- | :--- | :--- |
| **POST** | `/admin/transactions/borrow` | **Mencatat peminjaman** buku oleh anggota. |
| **POST** | `/admin/transactions/return/{borrowing}` | **Memproses pengembalian** buku. Menghitung dan mencatat denda (jika ada). |
| **POST** | `/admin/transactions/extend/{borrowing}` | **Memperpanjang** tanggal jatuh tempo pinjaman (hanya jika belum terlambat). |
| **GET** | `/admin/fines` | Mendapatkan daftar semua catatan denda. |
| **POST** | `/admin/fines/{fine}/pay` | **Mencatat pembayaran** denda. |

### POST /api/admin/transactions/borrow

Endpoint ini mencatat peminjaman baru dan mengurangi stok buku.

| Parameter | Tipe | Wajib? | Deskripsi |
| :--- | :--- | :--- | :--- |
| `user_id` | integer | Ya | ID Anggota yang meminjam. |
| `book_id` | integer | Ya | ID Buku yang dipinjam. |

**Response Sukses (201):**
```json
{
  "message": "Peminjaman berhasil dicatat.",
  "data": {
    "id": 1,
    "user": { },
    "book": { },
    "borrow_date": "2025-11-19",
    "due_date": "2025-11-26",
    "status": "borrowed"
  }
}
```
## 5. Laporan & Statistik (Admin Only)

**Middleware:** `auth:sanctum`, `role:admin`

| Method | Endpoint | Deskripsi |
| :--- | :--- | :--- |
| **GET** | `/admin/reports/borrowing` | Laporan statistik peminjaman (total pinjam, kembali, terlambat, tren 30 hari). |
| **GET** | `/admin/reports/top-books` | Laporan 10 buku terlaris/paling sering dipinjam. |
| **GET** | `/admin/reports/financial` | Laporan denda (total denda terbayar, belum terbayar, tren bulanan). |

### GET /api/admin/reports/financial

**Response Sukses (200):**
```json
{
  "message": "Laporan keuangan denda berhasil diambil.",
  "summary": {
    "total_paid_fine": 150000,
    "total_unpaid_fine": 25000,
    "grand_total_fine": 175000
  },
  "monthly_fines": [
    { "year": 2025, "month": 11, "total_fines": 80000 },
    { "year": 2025, "month": 10, "total_fines": 70000 }
  ]
}
```
