# Referensi API

Base URL produksi: `https://apimann.gondrii.my.id/api`
Semua permintaan dan balasan berformat **JSON**. Sertakan header `Accept: application/json`.

## Autentikasi

Endpoint selain `login` dan `verify` memerlukan header:

```http
Authorization: Bearer <access_token>
```

Token diterbitkan Sanctum, **kedaluwarsa 7 hari**, dan dihapus saat logout.

### Login

```http
POST /api/login
Content-Type: application/json

{
  "username": "admin_utama",
  "password": "••••••••"
}
```

Balasan `200`:

```json
{
  "message": "Login berhasil.",
  "access_token": "1|xxxxxxxxxxxxxxxx",
  "token_type": "Bearer",
  "role": "Admin",
  "user": {
    "id": "01a110e0-...",
    "username": "admin_utama",
    "role": { "name": "Admin" },
    "employee": { "nik": "EMP-001", "name": "Administrator", "position": "Kepala Admin" }
  }
}
```

!!! warning "Perlindungan brute force"
    Login dibatasi **5 percobaan/menit per kombinasi username+IP** dan **20/menit per IP**.
    Pesan gagal selalu generik (`"Username atau password salah."`) agar penyerang tidak bisa
    menebak username mana yang ada. Akun dengan `is_active = false` ditolak meski tokennya valid.

### Logout & profil

| Metode | Endpoint | Keterangan |
|---|---|---|
| `POST` | `/api/logout` | mencabut token yang sedang dipakai |
| `GET` | `/api/profile` | data user + role + employee |

## Hak akses per peran

| Peran | Cakupan |
|---|---|
| **Admin** | seluruh CRUD master + payroll + dashboard admin |
| **Owner** | baca SPK/payroll/slip + laporan + dashboard owner |
| **Karyawan** | hanya data miliknya sendiri (slip, tugas, kasbon, dashboard) |

Middleware `role:` memeriksa peran **dan** memastikan karyawan nonaktif tidak bisa mengakses.

## Master data (Admin)

| Metode | Endpoint | Keterangan |
|---|---|---|
| `GET/POST` | `/api/shops` | daftar / tambah toko |
| `GET/PUT/DELETE` | `/api/shops/{id}` | detail / ubah / hapus |
| `GET/POST` | `/api/employees` | daftar / tambah karyawan **+ akun login** |
| `GET/PUT/DELETE` | `/api/employees/{id}` | detail / ubah / hapus |
| `GET` | `/api/employees/{id}/cash-advances-total` | total kasbon seorang karyawan |

Contoh tambah karyawan:

```json
{
  "nik": "EMP-010",
  "name": "Budi Santoso",
  "position": "Teknisi",
  "phone": "0812...",
  "username": "budi",
  "password": "rahasia123",
  "is_active": true
}
```

!!! tip "Ubah karyawan tanpa reset password"
    Pada `PUT /api/employees/{id}`, kolom `password` boleh dikosongkan — password lama
    dipertahankan. (Dulu selalu gagal 422 karena validasi mewajibkan password.)

## SPK

| Metode | Endpoint | Keterangan |
|---|---|---|
| `GET/POST` | `/api/spks` | daftar (filter `status`) / tambah |
| `GET/PUT/DELETE` | `/api/spks/{id}` | detail / ubah / hapus |
| `GET` | `/api/karyawan/spks` | tugas milik karyawan yang login |
| `GET` | `/api/owner/spks` | daftar SPK untuk owner |

```json
{
  "date": "2026-10-18",
  "shop_id": "uuid-toko",
  "employee_id": "uuid-karyawan",
  "job_name": "Neon Box Depan Toko",
  "spk_value": 3500000,
  "job_status": "Selesai",
  "payment_status": "Belum Dibayar"
}
```

## Kasbon

| Metode | Endpoint | Keterangan |
|---|---|---|
| `GET/POST` | `/api/cash-advances` | daftar / catat kasbon |
| `GET/PUT/DELETE` | `/api/cash-advances/{id}` | detail / ubah / hapus |
| `GET` | `/api/karyawan/cash-advances` | kasbon milik karyawan |

## Payroll (slip gaji)

| Metode | Endpoint | Keterangan |
|---|---|---|
| `POST` | `/api/payrolls/preview` | **hitung tanpa menyimpan** |
| `GET/POST` | `/api/payrolls` | daftar / buat slip |
| `GET/PUT/DELETE` | `/api/payrolls/{id}` | detail / ubah / hapus |
| `PATCH` | `/api/payrolls/{id}/payment` | tandai dibayar |
| `GET` | `/api/payrolls/{id}/pdf` | unduh slip PDF |
| `GET` | `/api/karyawan/payrolls` | slip milik karyawan |
| `GET` | `/api/owner/payrolls` | slip untuk owner |

### Pratinjau perhitungan

```http
POST /api/payrolls/preview
{ "employee_id": "uuid", "period": "2026-10" }
```

```json
{
  "employee": { "id": "uuid", "name": "Budi", "nik": "EMP-001" },
  "period": "2026-10",
  "spk_count": 2,
  "items": [
    { "spk_number": "SPK-BE-CO/2026/03/016/004390", "spk_value": 2500000,
      "slot_count": 4, "duration_days": 1, "days_worked": 1,
      "daily_rate": 625000, "share_amount": 625000 },
    { "spk_number": "SPK-BE-CO/2026/03/016/004881", "spk_value": 2500000,
      "slot_count": 3, "duration_days": 1, "days_worked": 1,
      "daily_rate": 833333.33, "share_amount": 833333.33 }
  ],
  "gross_share": 1458333.33,
  "ppn_rate": 4, "ppn_amount": 58333.33,
  "commission_rate": 22, "commission_amount": 308000,
  "meal_rate": 100000, "meal_count": 2, "meal_deduction": 200000,
  "cash_advance_deduction": 0,
  "net_salary": 892000,
  "is_negative": false
}
```

Rincian rumusnya ada di halaman [Aturan Penggajian](penggajian.md).

### Buat slip massal

```http
POST /api/payrolls/generate
{ "period": "2026-10" }
```

Membuat slip untuk **semua karyawan** yang punya SPK pada periode itu. Karyawan yang sudah punya
slip dilewati.

```json
{
  "message": "3 slip dibuat, 0 dilewati.",
  "data": { "period": "2026-10", "created_count": 3, "skipped_count": 0, "created": [ ... ] }
}
```

### Tandai dibayar

```http
PATCH /api/payrolls/{id}/payment
{ "payment_date": "2026-10-18" }
```

Efek: `payment_status` menjadi `Sudah Dibayar` dan notifikasi `payroll_paid` dikirim ke karyawan.
Status SPK **tidak** ikut berubah — status pekerjaan dan pembayaran SPK diatur terpisah oleh admin.


## Notifikasi

| Metode | Endpoint | Keterangan |
|---|---|---|
| `GET` | `/api/notifications` | riwayat notifikasi + jumlah belum dibaca |
| `PATCH` | `/api/notifications/{id}/read` | tandai satu sudah dibaca |
| `POST` | `/api/notifications/read-all` | tandai semua dibaca |
| `POST` | `/api/device-token` | daftarkan token FCM perangkat |
| `DELETE` | `/api/device-token` | hapus token (dipanggil saat logout) |

```json
{ "token": "fcm-token-perangkat", "platform": "android" }
```

## Verifikasi slip (publik)

```http
GET /api/verify/{verification_token}
```

Mengembalikan ringkasan slip (nomor, periode, nama karyawan, NIK **disamarkan**, gaji bersih,
status bayar) tanpa perlu login — dipakai saat QR pada slip dipindai.

## Dashboard & laporan

| Metode | Endpoint | Peran |
|---|---|---|
| `GET` | `/api/admin/dashboard` | Admin |
| `GET` | `/api/owner/dashboard` | Owner |
| `GET` | `/api/karyawan/dashboard` | Karyawan |
| `GET` | `/api/reports/spk` · `/api/reports/payroll` · `/api/reports/cash-advances` | Admin, Owner |
| `GET` | `/api/owner/reports/*` | Owner |

## Format kesalahan

| Kode | Arti | Contoh balasan |
|---|---|---|
| `401` | belum/tidak terautentikasi | `{"message":"Unauthenticated."}` |
| `403` | peran tidak diizinkan | `{"message":"Akses ditolak."}` |
| `404` | data/endpoint tidak ada | `{"message":"Data tidak ditemukan."}` |
| `422` | validasi gagal | `{"message":"...","errors":{...}}` |
| `429` | terlalu banyak permintaan | `{"message":"Too Many Attempts."}` |

Respons kesalahan **selalu JSON** dan tidak pernah memuat stack trace, karena `APP_DEBUG=false`
di produksi dan penanganan pengecualian di `bootstrap/app.php` memaksa keluaran JSON.
