# Deploy ke Shared Hosting (cPanel, tanpa SSH)

Panduan ini untuk paket seperti **Rumahweb Hosting Small** — ada cPanel, FTP, MySQL, cron,
tapi **tidak ada akses SSH**. Deploy tetap otomatis lewat GitHub Actions (FTP + deploy hook).

> Intinya: **CI tetap di GitHub Actions** (test & build), **CD-nya lewat FTP**, bukan SSH.
> Cron **tidak** dipakai untuk deploy — cron hanya perlu kalau nanti memakai queue/scheduler.

---

## 0. Yang perlu disiapkan

| Dari cPanel | Contoh |
|---|---|
| Domain | `payroll.namadomain.com` |
| FTP host | `ftp.namadomain.com` |
| FTP user & password | `namadomain` / ••• |
| Database | `namadomain_payroll` (nama otomatis berprefix) |
| DB user & password | `namadomain_payroll` / ••• |
| Versi PHP | **8.3 (ea-php83)** — minimal 8.2 karena Laravel 12 butuh PHP ≥ 8.2 |

---

## 1. Atur versi PHP

cPanel → **MultiPHP Manager** → centang domain → pilih **ea-php83** → Apply.

> **Penting:** jangan pakai `alt-php*` (CloudLinux). Di paket ini `alt-php83` datang tanpa
> ekstensi apa pun (intl/zip/gd/pdo_mysql kosong) dan harus diaktifkan satu per satu.
> `ea-php83` sudah lengkap sejak awal — sudah diuji: PHP 8.3.30 dengan intl, zip, gd,
> pdo_mysql, mbstring, bcmath, dom, fileinfo semuanya aktif.

Cek di cPanel → **Select PHP Version** bahwa ekstensi ini aktif:
`pdo_mysql, mbstring, dom/xml, curl, fileinfo, gd, zip, intl, bcmath, openssl`.

> Kalau `intl`/`zip` tidak ada, PDF slip dan composer bisa bermasalah — hubungi support Rumahweb
> (biasanya bisa diaktifkan).

---

## 2. Buat database

cPanel → **MySQL Databases**:

1. Buat database → `payroll`
2. Buat user + password kuat
3. **Add User to Database** → centang **ALL PRIVILEGES**

Catat nama lengkapnya (cPanel menambahkan prefix akun, mis. `namadomain_payroll`).

---

## 3. Siapkan folder

Lewat **File Manager**, buat di home direktori (bukan di dalam `public_html`):

```
/home/USERNAME/
├── app/        <- root Laravel (diisi otomatis oleh deploy)
└── deploy/     <- tempat release.zip & token
```

---

## 4. Buat berkas `.env`

Di komputer (repo backend), buat kunci aplikasi:

```bash
php artisan key:generate --show
```

Lalu buat berkas `.env` dan unggah ke **`~/app/.env`** lewat File Manager:

```env
APP_NAME="Mann Plus Payroll"
APP_ENV=production
APP_KEY=base64:HASIL_DARI_PERINTAH_DI_ATAS
APP_DEBUG=false
APP_URL=https://payroll.namadomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=namadomain_payroll
DB_USERNAME=namadomain_payroll
DB_PASSWORD=password_database_kamu

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Notifikasi push (unggah berkas service account ke storage/app/firebase/)
FIREBASE_CREDENTIALS=/home/USERNAME/app/storage/app/firebase/service-account.json
FIREBASE_PROJECT_ID=mann-plus

# Password awal akun admin — dipakai sekali saat deploy pertama (minimal 8 karakter).
# Setelah bisa login, ganti password dari aplikasi.
INIT_ADMIN_PASSWORD=ganti_dengan_password_kuat
```

> **Penting:** `APP_DEBUG=false` di server produksi. Kalau `true`, stack trace bisa terbaca publik.
> **Penting:** unggah juga service account Firebase ke `~/app/storage/app/firebase/service-account.json`
> supaya push notification jalan.

---

## 5. Arahkan document root ke `app/public`

cPanel → **Domains** → domain → **Manage** → ubah **Document Root** menjadi:

```
/home/USERNAME/app/public
```

Kalau cPanel menolak (beberapa paket mengunci docroot domain utama), pakai cara shim:

**a. Buat `public_html/index.php`**

```php
<?php

require __DIR__.'/../app/public/index.php';
```

**b. Buat `public_html/.htaccess`**

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [L]
</IfModule>
```

---

## 6. Pasang penerima deploy

1. Unggah `deploy/deploy.php` (dari repo) ke **`public_html/deploy.php`**.
2. Buat **`~/deploy/token.txt`** berisi token acak panjang, satu baris, tanpa spasi:

   ```bash
   php -r "echo bin2hex(random_bytes(24));"
   ```

3. Pastikan folder `~/deploy` bisa ditulis lewat FTP (dipakai untuk `release.zip` dan `deploy.log`).

---

## 7. Isi secret di GitHub

Repo backend → **Settings → Secrets and variables → Actions**:

**Secrets**

| Nama | Isi |
|---|---|
| `FTP_HOST` | `ftp.namadomain.com` |
| `FTP_USER` | user FTP cPanel |
| `FTP_PASS` | password FTP |
| `DEPLOY_URL` | `https://payroll.namadomain.com/deploy.php` |
| `DEPLOY_TOKEN` | isi `token.txt` |

**Variables**

| Nama | Isi |
|---|---|
| `DEPLOY_ENABLED` | `true` |

> Kalau hosting hanya mendukung FTP biasa (bukan FTPS), hapus baris `--ssl-reqd`
> di `.github/workflows/deploy.yml`. Sebaiknya tetap pakai FTPS kalau tersedia.

---

## 8. Jalankan deploy

> **Deploy pertama (belum ada `vendor/`):** `deploy.php` butuh Laravel untuk menjalankan
> migrasi, sedangkan `vendor/` belum ada. Untuk pemasangan awal, unggah `deploy/bootstrap.php`
> ke `public_html/bootstrap.php` lalu buka `https://domain/bootstrap.php` **sekali**.
> Berkas itu menarik kode dari GitHub, menjalankan `composer install --no-dev` di dalam PHP
> (tanpa `shell_exec`), lalu `migrate` + `app:init-admin`. Ia menghapus dirinya sendiri.
> Kalau ada perintah artisan yang perlu diulang, pakai `deploy/artisan.php`
> (`?cmd=migrate`, `?cmd=init`, `?cmd=optimize`, `?cmd=status`) lalu hapus berkasnya.

GitHub → **Actions** → **Deploy ke Hosting** → **Run workflow**.

Yang terjadi:

1. Runner memasang dependensi produksi (`composer install --no-dev`).
2. Aplikasi dipaketkan jadi `release.zip`.
3. Zip diunggah lewat FTPS ke `~/deploy/release.zip`.
4. Runner memanggil `deploy.php` → ekstrak ke `~/app` → `migrate --force` → `app:init-admin`
   (membuat akun admin pertama kalau belum ada) → `optimize`.

Cek hasilnya:

```bash
curl https://payroll.namadomain.com/up      # harus 200
curl -X POST https://payroll.namadomain.com/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin_utama","password":"password123"}'
```

---

## 9. Setelah deploy pertama

1. **Login admin, ganti password bawaan** (password seed `password123` hanya untuk demo).
2. Arahkan aplikasi mobile ke domain produksi:
   `flutter build apk --release --dart-define=API_BASE_URL=https://payroll.namadomain.com`
3. Cek `~/deploy/deploy.log` bila ada yang gagal.
4. **Amankan `deploy.php`**: ganti token secara berkala, atau hapus berkasnya setelah deploy
   terakhir (deploy berikutnya tinggal unggah ulang lewat File Manager).

---

## Kenapa tidak pakai cron untuk deploy?

Cron **bisa** menjalankan perintah terjadwal, tapi untuk deploy punya kelemahan:

| | Cron (pull) | FTP + hook (push) |
|---|---|---|
| Kapan jalan | menunggu jadwal (telat 1–5 menit) | langsung setelah CI lulus |
| Gerbang tes | tidak ada | wajib lulus `pint` + 50 test dulu |
| Butuh di server | `git` **dan** `composer` | hanya PHP + ZipArchive |
| Kalau gagal di tengah | aplikasi bisa setengah jadi | zip utuh, ekstrak sekali jalan |
| Status keberhasilan | tidak terlihat | job GitHub hijau/merah |

Cron tetap berguna untuk hal lain (dan aplikasi ini **belum membutuhkannya** karena notifikasi
dikirim langsung, bukan lewat queue). Kalau nanti memakai queue:

```
* * * * * cd /home/USERNAME/app && /usr/local/bin/php artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```

---

## Batasan paket murah yang perlu diketahui

1. **PHP 8.3 (ea-php83)** — sudah diuji lengkap ekstensinya. Jangan pakai `alt-php*` tanpa
   mengaktifkan ekstensi dulu, dan jangan turun ke 7.4 (Laravel 12 butuh ≥ 8.2).
2. **1 GB penyimpanan** — aplikasi + vendor ± 120 MB, sisanya untuk log & backup. Bersihkan
   `storage/logs` berkala.
3. **1 core / 1 GB RAM** — cukup untuk demo & pemakaian bengkel kecil; jangan pasang proses berat
   (mis. render PDF massal sekaligus).
4. **Tanpa SSH** — semua perintah artisan dijalankan lewat `deploy.php`, bukan terminal.
