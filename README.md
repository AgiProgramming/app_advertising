# API Penggajian Mann Plus Advertising

Backend REST API untuk aplikasi penggajian karyawan bengkel **Mann Plus Advertising**
(Neon Box · Billboard · Media Promosi). Dibangun dengan Laravel 12 + Sanctum, dipakai oleh
aplikasi mobile Flutter `app_advertising_mobile`.

> Skripsi: *Analisis dan Optimalisasi Fitur Penggajian Karyawan Menggunakan Pendekatan DevOps*

<!-- Badge pipeline (repo: AgiProgramming/app_advertising) -->
![Backend CI](https://github.com/AgiProgramming/app_advertising/actions/workflows/ci.yml/badge.svg)

## Teknologi

| Bagian | Teknologi |
|---|---|
| Framework | Laravel 12 (PHP 8.4) |
| Autentikasi | Laravel Sanctum (token, kedaluwarsa 7 hari) |
| Basis data | MySQL 8.0 |
| Slip PDF | barryvdh/laravel-dompdf |
| QR verifikasi | simplesoftwareio/simple-qrcode |
| Push notifikasi | Firebase Cloud Messaging (HTTP v1) |
| Pengujian | PHPUnit — 53 test / 158 assertion |
| Format kode | Laravel Pint |

## Fitur utama

- **Login berbasis peran** — Admin, Owner, Karyawan (`/api/login`, rate limit 5/menit per username+IP).
- **SPK** (Surat Perintah Kerja) — CRUD, status pekerjaan & pembayaran.
- **Penggajian** — preview perhitungan → simpan slip → tandai dibayar.
  Rumus: `PPN 3%` → `nilai bersih` → `komisi 22%` + tunjangan − potongan kasbon.
- **Kasbon** — pencatatan pinjaman, potongan otomatis dibatasi sisa utang.
- **Slip PDF** — `GET /api/payrolls/{id}/pdf` (struk + QR).
- **Verifikasi publik** — `GET /api/verify/{token}` (NIK disamarkan).
- **Notifikasi** — feed in-app + push FCM saat slip terbit/dibayar.
- **Data master** — karyawan (sekaligus akun login) dan toko.

## Menjalankan

```bash
composer install
cp .env.example .env
php artisan key:generate

# siapkan database MySQL lalu:
php artisan migrate --seed
php artisan serve
```

Butuh MySQL 8 yang berjalan. Konfigurasi ada di `.env` (`DB_*`).

## Pengujian

```bash
php artisan test          # 53 test / 158 assertion
vendor/bin/pint --test    # cek standar format
```

Pengujian memakai database `app_advertising_test` (lihat `phpunit.xml`).

## Struktur

```
app/
├── Http/Controllers/Api/     # Auth, SPK, Payroll, Kasbon, Karyawan, Toko, Notifikasi, Verify
├── Http/Middleware/          # RoleMiddleware, SecurityHeaders
├── Models/                   # User, Employee, Shop, Spk, Payroll, CashAdvance, AppNotification
├── Repositories/             # akses data (interface + implementasi Eloquent)
├── Services/                 # PayrollService, NotificationService, FcmService, dll
└── Http/Resources/           # bentuk respons JSON
resources/views/pdf/slip.blade.php   # template struk PDF
tests/Feature/                       # 50 pengujian otomatis
.github/workflows/                   # pipeline CI/CD
```

## Dokumentasi terkait

- `LAPORAN_AUDIT_API.md` — hasil audit keamanan API (90 skenario) & perbaikannya.
- `deploy/README.md` — panduan deploy ke shared hosting (cPanel, tanpa SSH) lewat FTP + deploy hook.
- `CI-CD.md` — penjelasan pipeline CI/CD (disertakan di paket skripsi, satu tingkat di atas repo ini).

## Catatan keamanan

- Tidak ada akun/backdoor khusus; login hanya lewat tabel `users`.
- Pesan gagal login bersifat generik (mencegah penebakan username).
- Akun nonaktif ditolak meski token masih valid.
- Respons error selalu JSON (tanpa stack trace).

test