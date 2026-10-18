# Teknologi yang Dipakai

## Backend (API)

| Teknologi | Versi | Dipakai untuk | Alasan dipilih |
|---|---|---|---|
| **PHP** | 8.3 (`ea-php83`) | Bahasa server | Versi tertinggi yang stabil di hosting; Laravel 12 mensyaratkan ≥ 8.2 |
| **Laravel** | 12 | Framework API | Routing, ORM, validasi, dan ekosistem pengujian yang lengkap |
| **MySQL** | 8.0 | Basis data | Tersedia di paket hosting; mendukung transaksi & foreign key |
| **Laravel Sanctum** | 4 | Autentikasi token | Ringan untuk API mobile, token bisa kedaluwarsa |
| **barryvdh/laravel-dompdf** | 3.1 | Slip PDF | Membuat PDF dari Blade tanpa dependensi biner |
| **simplesoftwareio/simple-qrcode** | 2 | QR verifikasi slip | Menghasilkan QR (SVG) untuk memeriksa keaslian slip |
| **Firebase Cloud Messaging** | HTTP v1 | Push notification | Notifikasi slip langsung ke perangkat karyawan |
| **Laravel Pint** | bawaan | Standar format kode | Penegak gaya kode pada pipeline CI |

!!! note "Kenapa FCM HTTP v1 tanpa SDK"
    Pengiriman push memakai HTTP v1 + JWT RS256 yang dibuat manual dari service account
    (`app/Services/FcmService.php`). Tujuannya menekan jumlah dependensi dan memudahkan
    penjelasan di skripsi, tanpa mengorbankan dukungan resmi Google.

## Aplikasi Mobile

| Teknologi | Versi | Dipakai untuk |
|---|---|---|
| **Flutter** | 3.41 | Kerangka aplikasi Android |
| **Dart** | 3.x | Bahasa aplikasi |
| **provider** | 6.x | Manajemen status (`AuthState`, `NotificationState`) |
| **http** | 1.x | Klien HTTP dengan pembungkus `ApiClient` sendiri |
| **shared_preferences** | 2.x | Menyimpan token login di perangkat |
| **intl** | 0.20 | Format rupiah dan tanggal Indonesia |
| **firebase_core / firebase_messaging** | 3.15 / 15.2 | Push notification |
| **flutter_local_notifications** | 19.x | Menampilkan notifikasi saat aplikasi aktif |
| **qr_flutter** | 4.x | Menampilkan QR pada slip |
| **share_plus + path_provider** | 11.x / 2.x | Menyimpan & membagikan slip PDF |

Font yang dipakai: **Instrument Sans** (disertakan lokal di `assets/fonts`, tidak bergantung internet).

## Infrastruktur

| Komponen | Keterangan |
|---|---|
| **Hosting** | Shared hosting cPanel (paket kecil) — **tanpa akses SSH** |
| **Web server** | Apache + PHP `ea-php83` (ekstensi intl, zip, gd, pdo_mysql, mbstring, bcmath, dom aktif) |
| **SSL** | Let's Encrypt (AutoSSL cPanel) — termasuk `apimann.gondrii.my.id` |
| **Transfer deploy** | FTPS (explicit TLS) ke `manaslu.iixcp.rumahweb.net` |
| **CI/CD** | GitHub Actions (runner `ubuntu-latest`) |
| **Distribusi APK** | GitHub Release (aset `mann-plus-payroll-v1.0.0.apk`) |

!!! warning "Batas paket hosting yang perlu diketahui"
    - **Tanpa SSH** → semua perintah artisan dijalankan lewat berkas `deploy/deploy.php`
      (bootstrap kernel Laravel, bukan `shell_exec` yang dimatikan).
    - **`exec()`/`shell_exec()` dimatikan** → skrip pemasangan menghapus folder dengan PHP murni.
    - **`alt-php83` datang tanpa ekstensi** → wajib memakai `ea-php83`.
    - **1 GB penyimpanan** → `storage/logs` perlu dibersihkan berkala.

## Perangkat pengembangan

| Perangkat | Versi |
|---|---|
| Windows + Git Bash | — |
| PHP lokal | 8.4.11 (kompatibel dengan server 8.3) |
| Composer | 2.x |
| Flutter SDK | 3.41.9 |
| Emulator | Pixel 10 Pro XL, Android 17, x86_64 |
