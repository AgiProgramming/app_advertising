# Arsitektur & Struktur Data

## Alur sistem

```
┌──────────────────────┐        HTTPS/JSON         ┌─────────────────────────┐
│  Aplikasi Flutter    │ ────────────────────────► │  REST API (Laravel)     │
│  (karyawan/admin/    │ ◄──────────────────────── │  apimann.gondrii.my.id  │
│   owner)             │      token Sanctum        └───────────┬─────────────┘
└──────────┬───────────┘                                       │
           │                                          ┌────────▼─────────┐
           │  push (FCM)                              │  MySQL 8         │
           │                                          │  (hosting)       │
           ▼                                          └──────────────────┘
┌──────────────────────┐                                       ▲
│  Firebase Cloud      │                                       │
│  Messaging (Google)  │                                       │
└──────────────────────┘                                       │
                                                     ┌─────────┴──────────┐
                                                     │ GitHub Actions     │
                                                     │ CI + CD (FTPS)     │
                                                     └────────────────────┘
```

**Pemisahan tanggung jawab**

- **Controller** hanya menangani permintaan/balasan HTTP dan validasi.
- **Service** memuat aturan bisnis (perhitungan gaji, notifikasi).
- **Repository** memuat akses data (interface + implementasi Eloquent) sehingga mudah diuji.
- **Resource** menentukan bentuk JSON yang dikirim ke aplikasi.

## Struktur folder backend

```
app_advertising/
├── app/
│   ├── Console/Commands/InitAdmin.php     # siapkan admin pertama saat deploy
│   ├── Http/
│   │   ├── Controllers/Api/               # Auth, Shop, Employee, Spk, CashAdvance,
│   │   │                                  # Payroll, Notification, Dashboard, Report
│   │   ├── Middleware/                    # RoleMiddleware, SecurityHeaders
│   │   ├── Requests/                      # validasi per modul
│   │   └── Resources/                     # bentuk respons JSON
│   ├── Models/                            # User, Role, Employee, Shop, Spk,
│   │                                      # CashAdvance, Payroll, AppNotification, DeviceToken
│   ├── Repositories/                      # Contracts + Eloquent
│   └── Services/                          # PayrollService, NotificationService, FcmService, ...
├── config/services.php                    # kredensial Firebase
├── database/migrations/                   # 13 migrasi
├── deploy/                                # skrip pemasangan untuk hosting tanpa SSH
│   ├── deploy.php                         # penerima rilis (dipanggil pipeline)
│   ├── bootstrap.php                      # pemasangan pertama (unduh + composer)
│   ├── artisan.php                        # jalankan perintah artisan tertentu
│   └── README.md                          # panduan cPanel
├── docs/                                  # dokumentasi ini (MkDocs)
├── resources/views/pdf/slip.blade.php     # template struk PDF
├── routes/api.php                         # seluruh endpoint
├── tests/                                 # 53 pengujian otomatis
└── .github/workflows/                     # ci.yml, deploy.yml, docs.yml
```

## Struktur folder mobile

```
app_advertising_mobile/
├── lib/
│   ├── core/          # theme.dart, formatters.dart (rupiah/tanggal), api_config.dart, api_client.dart
│   ├── models/        # User, Payroll, Spk, CashAdvance, Shop, Employee
│   ├── services/      # api_services.dart (pemetaan endpoint), push_service.dart (FCM)
│   ├── state/         # auth_state.dart, notification_state.dart
│   ├── screens/       # login, shell, home (per peran), payroll, spk, cash_advance,
│   │                  # notifications, profile, master
│   └── widgets/       # komponen UI bersama (StatTile, StatusBadge, EmptyState, ...)
├── assets/fonts/      # Instrument Sans
├── test/              # 8 pengujian widget/unit + 2 uji integrasi (manual)
└── DESIGN.md          # panduan visual
```

## Skema database

### `users` & `roles`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | uuid | kunci utama |
| `role_id` | uuid | relasi ke `roles` |
| `username` | string | unik, dipakai login (tidak peka huruf besar/kecil) |
| `password` | string | hash bcrypt |
| `deleted_at` | timestamp | soft delete |

`roles` berisi tiga baris: **Admin**, **Owner**, **Karyawan**.

### `employees`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `user_id` | uuid | akun login karyawan |
| `nik` | string | nomor induk karyawan |
| `name`, `position`, `phone`, `address` | string | data pribadi |
| `is_active` | boolean | karyawan nonaktif tidak bisa login |

### `shops`

`code`, `name`, `owner_name`, `status` — data toko tempat SPK dikerjakan.

### `spks` (Surat Perintah Kerja)

| Kolom | Tipe | Keterangan |
|---|---|---|
| `spk_number` | string | otomatis, format `SPK-YYYYMMDD-XXX` |
| `date` | date | tanggal pekerjaan |
| `shop_id`, `employee_id` | uuid | toko & pelaksana |
| `job_name`, `description`, `notes` | text | uraian pekerjaan |
| `spk_value` | decimal | nilai pekerjaan (dasar perhitungan gaji) |
| `job_status` | enum | `Belum Dikerjakan` / `Selesai` |
| `payment_status` | enum | `Belum Dibayar` / `Sudah Dibayar` |

### `cash_advances` (kasbon)

`employee_id`, `amount`, `date`, `description`.

### `payrolls` (slip gaji)

| Kolom | Tipe | Keterangan |
|---|---|---|
| `slip_number` | string | otomatis, format `SLIP-YYYYMMDD-XXX` |
| `spk_id`, `employee_id` | uuid | sumber perhitungan |
| `period` | string | `YYYY-MM` |
| `original_spk_value` | decimal | nilai SPK apa adanya |
| `net_spk_value` | decimal | setelah dipotong PPN 3% |
| `salary_percentage` | decimal | persentase komisi (default 22) |
| `meal_allowance` | decimal | tunjangan makan |
| `cash_advance_deduction` | decimal | potongan kasbon (dibatasi sisa utang) |
| `net_salary` | decimal | **gaji bersih** yang diterima |
| `payment_status`, `payment_date` | enum / date | status pembayaran |
| `verification_token` | uuid | dipakai QR verifikasi |

### `app_notifications` & `device_tokens`

- `app_notifications`: riwayat notifikasi in-app (`user_id`, `title`, `body`, `type`, `payroll_id`, `read_at`).
- `device_tokens`: token FCM per perangkat (`user_id`, `token`, `platform`).

## Alur status

| Kejadian | Efek |
|---|---|
| Slip dibuat | `payrolls` baru + notifikasi `payroll_issued`; status SPK disimpan sebagai `previous_job_status` |
| Slip dibayar | `payment_status = Sudah Dibayar`, `payment_date` diisi, SPK menjadi `Selesai` + `Sudah Dibayar`, notifikasi `payroll_paid` |
| Slip dihapus | status SPK dikembalikan ke `previous_job_status` (rollback) |
