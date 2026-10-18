# Deployment

Sistem berjalan di **shared hosting cPanel tanpa akses SSH** (paket kecil). Karena tidak ada
terminal, semua perintah artisan dijalankan lewat skrip PHP yang dipanggil dari browser atau pipeline.

## Ringkasan tata letak di server

```
/home/gonn2842/
├── app/                      # root Laravel (diisi oleh deploy)
│   ├── .env                  # dibuat manual sekali (tidak ikut paket rilis)
│   ├── storage/app/firebase/service-account.json
│   └── vendor/               # dipasang oleh pipeline (composer install --no-dev)
├── deploy/                   # di luar web root
│   ├── token.txt             # token untuk memanggil deploy.php
│   ├── admin-password.txt    # password admin pertama (hapus setelah dipakai)
│   └── release.zip           # paket rilis yang diunggah pipeline
└── public_html/              # document root domain
    ├── index.php             # shim → ../app/public/index.php
    ├── .htaccess             # rewrite ke index.php
    └── deploy.php            # penerima rilis (token-protected)
```

## Pemasangan pertama

### 1. Versi PHP & ekstensi

cPanel → **MultiPHP Manager** → centang domain → pilih **`ea-php83`** → Apply.

!!! warning "Jangan pakai `alt-php*`"
    Pada paket ini `alt-php83` datang **tanpa ekstensi apa pun** (intl, zip, gd, pdo_mysql kosong).
    `ea-php83` sudah lengkap. Verifikasi cepat dengan berkas `phpinfo` sementara:

    ```
    PHP 8.3.x — intl ✓ zip ✓ gd ✓ pdo_mysql ✓ mbstring ✓ bcmath ✓ dom ✓ fileinfo ✓
    ```

### 2. Database

cPanel → **MySQL Databases** → buat database + user, beri **ALL PRIVILEGES**.
Nama otomatis berprefix akun, contoh `gonn2842_payroll`.

### 3. Berkas `.env`

Unggah ke `~/app/.env`. Kunci aplikasi dibuat di komputer:

```bash
php artisan key:generate --show
```

Isi minimal:

```env
APP_NAME="Mann Plus Payroll"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://apimann.gondrii.my.id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gonn2842_payroll
DB_USERNAME=gonn2842_payroll
DB_PASSWORD=...

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

FIREBASE_CREDENTIALS=/home/gonn2842/app/storage/app/firebase/service-account.json
FIREBASE_PROJECT_ID=mann-plus

INIT_ADMIN_PASSWORD=...   # dipakai sekali oleh app:init-admin
```

!!! danger "`APP_DEBUG` wajib `false`"
    Dengan `APP_DEBUG=true`, halaman error Laravel menampilkan stack trace, kredensial database,
    dan struktur folder ke publik.

### 4. Document root & routing

Cara utama: cPanel → **Domains** → ubah document root ke `~/app/public`.

Bila ditolak (beberapa paket mengunci docroot domain utama), pakai shim:

`public_html/index.php`

```php
<?php

require __DIR__.'/../app/public/index.php';
```

`public_html/.htaccess`

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [L]
</IfModule>
```

### 5. Penerima deploy

Unggah `deploy/deploy.php` ke `public_html/deploy.php`, lalu buat `~/deploy/token.txt`:

```bash
php -r "echo bin2hex(random_bytes(24));"
```

### 6. Deploy pertama (belum ada `vendor/`)

`deploy.php` membutuhkan Laravel untuk menjalankan migrasi, sedangkan `vendor/` belum ada.
Untuk pemasangan awal:

1. Unggah `deploy/bootstrap.php` ke `public_html/bootstrap.php`
2. Buka `https://domain/bootstrap.php` **sekali**

Berkas itu menarik kode dari GitHub, menjalankan `composer install --no-dev` **di dalam PHP**
(tanpa `shell_exec`), lalu `migrate` + `app:init-admin`, dan menghapus dirinya sendiri.

!!! tip "Kalau perlu menjalankan artisan lagi"
    Unggah `deploy/artisan.php` lalu panggil `?cmd=migrate`, `?cmd=init`, `?cmd=optimize`,
    atau `?cmd=status`. Hapus berkasnya setelah selesai.

## Deploy berikutnya (otomatis)

Cukup jalankan workflow **Deploy ke Hosting** di GitHub Actions (atau push tag `v*`).
Pipeline melakukan: `composer install --no-dev` → paketkan `release.zip` → unggah via **FTPS** →
panggil `deploy.php` → `migrate --force` → `app:init-admin` → `optimize`.

## Pemeriksaan setelah deploy

```bash
curl https://apimann.gondrii.my.id/up          # harus 200
curl -X POST https://apimann.gondrii.my.id/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin_utama","password":"<password>"}'
```

## Daftar periksa keamanan

- [ ] `APP_DEBUG=false`
- [ ] password admin bawaan sudah diganti, `~/deploy/admin-password.txt` dihapus
- [ ] token `~/deploy/token.txt` panjang & dirahasiakan
- [ ] service account Firebase hanya ada di `storage/app/firebase/` (di luar web root)
- [ ] `.env` tidak pernah ikut terpaket dalam `release.zip`
- [ ] berkas bantuan (`bootstrap.php`, `artisan.php`) sudah dihapus setelah dipakai

## Batasan paket hosting

| Batas | Dampak | Mitigasi |
|---|---|---|
| Tanpa SSH | tidak bisa menjalankan artisan manual | `deploy.php`, `artisan.php` |
| `exec()` dimatikan | skrip tidak bisa memanggil shell | pembersihan folder dengan PHP murni |
| 1 GB penyimpanan | log bisa menumpuk | bersihkan `storage/logs` berkala |
| 1 core / 1 GB RAM | render PDF massal berat | buat slip satu per satu |
