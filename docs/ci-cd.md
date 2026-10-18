# CI/CD (Integrasi & Penerapan Berkelanjutan)

Semua pipeline ditulis sebagai kode di dalam repositori (**pipeline as code**) dan berjalan di
GitHub Actions.

## Empat pipeline

| Pipeline | Berkas | Repo | Pemicu | Isi |
|---|---|---|---|---|
| **Backend CI** | `.github/workflows/ci.yml` | `app_advertising` | push/PR ke `main`, manual | `pint --test` → `php artisan test` di MySQL 8 |
| **Deploy ke Hosting** | `.github/workflows/deploy.yml` | `app_advertising` | manual / tag `v*` | build → FTPS → `deploy.php` (migrate, init-admin, optimize) |
| **Mobile CI** | `.github/workflows/ci.yml` | `app_advertising_mobile` | push/PR ke `main`, manual | `flutter analyze` → `flutter test` → build APK |
| **Rilis APK** | `.github/workflows/rilis.yml` | `app_advertising_mobile` | tag `v*` | build APK → terbitkan GitHub Release |
| **Dokumentasi** | `.github/workflows/docs.yml` | `app_advertising` | push ke `main` (folder `docs/`) | build MkDocs → deploy ke GitHub Pages |

## Alur lengkap

```
developer push
      │
      ▼
Backend CI  (Pint + 53 test, MySQL 8)          ± 1m 12s
      │ hijau
      ▼
Deploy ke Hosting (FTPS + hook)                ± 1m 10s
      │  migrate → app:init-admin → optimize
      ▼
API produksi terbarui otomatis

git tag v1.0.0
      │
      ▼
Rilis APK (build + GitHub Release)             ± 5m 30s
      │
      ▼
APK siap dipasang di HP bengkel
```

## Detail job backend

**`gaya-kode`** — PHP 8.4 + Composer, cache `~/.composer/cache` dengan kunci `composer.lock`,
lalu `vendor/bin/pint --test`. Satu berkas saja tidak sesuai standar → job gagal (cepat, ±18 detik).

**`pengujian`** — service container `mysql:8.0` (healthcheck `mysqladmin ping`), PHP 8.4 +
ekstensi `pdo_mysql, mbstring, xml, dom, gd, zip, intl, bcmath` + Xdebug, lalu:

```bash
cp .env.example .env
php artisan key:generate
php artisan test                       # gerbang utama
php artisan test --coverage-clover ... # artefak cakupan (continue-on-error)
```

Ada loop kesiapan MySQL (30× percobaan) supaya tidak gagal acak pada runner yang masih dingin.

## Detail job deploy

1. `composer install --no-dev --prefer-dist --optimize-autoloader` dengan **PHP 8.3**
   (disamakan dengan hosting agar paket di `vendor/` cocok).
2. `zip` aplikasi (mengecualikan `.env`, `tests/`, `.git/`, `storage/logs/*`, `firebase/*`).
3. Unggah `release.zip` ke `~/deploy/` melalui **FTPS**:

   ```bash
   curl --fail --ssl-reqd --user "$FTP_USER:$FTP_PASS" \
        --ftp-create-dirs --upload-file release.zip \
        "ftp://$FTP_HOST/deploy/release.zip"
   ```

4. Unggah kredensial Firebase (bila secret `FIREBASE_SA_JSON` diisi).
5. Panggil `https://domain/deploy.php?token=...` → ekstrak, `migrate --force`,
   `app:init-admin`, `optimize`.

!!! note "Kenapa FTPS, bukan FTP biasa"
    Password FTP tidak boleh lewat jaringan dalam bentuk polos. Sertifikat TLS server FTP
    terbit untuk `manaslu.iixcp.rumahweb.net` — memakai nama host lain akan gagal dengan
    `SEC_E_WRONG_PRINCIPAL`.

## Secret & variable yang dibutuhkan

**Secrets** (Settings → Secrets and variables → Actions):

| Nama | Isi |
|---|---|
| `FTP_HOST` | `manaslu.iixcp.rumahweb.net` |
| `FTP_USER` | user FTP cPanel |
| `FTP_PASS` | password FTP |
| `DEPLOY_URL` | `https://apimann.gondrii.my.id/deploy.php` |
| `DEPLOY_TOKEN` | isi `~/deploy/token.txt` |
| `FIREBASE_SA_JSON` | isi service account JSON (opsional; tanpa ini push nonaktif) |

**Variables:**

| Nama | Isi |
|---|---|
| `DEPLOY_ENABLED` | `true` — tanpa ini job deploy **dilewati** (bukan gagal) |
| `API_BASE_URL` | opsional, menimpa alamat API pada build APK |

!!! warning "Job-level `if` tidak bisa membaca `secrets.*`"
    Karena itu sakelar deploy memakai **variable** `DEPLOY_ENABLED`, bukan secret. Memakai secret
    pada kondisi job membuat kondisi selalu salah sehingga job diam-diam tidak pernah jalan.

## Hasil terukur

| Pemeriksaan | Hasil |
|---|---|
| `vendor/bin/pint --test` | lulus (66 berkas dirapikan otomatis sebelumnya) |
| `php artisan test` | **53 test / 158 assertion**, ±3,4 detik |
| `flutter analyze` | **0 issue** |
| `flutter test` | **8 test** lulus |
| Backend CI (GitHub) | **success**, 1m 12s, 1 artefak |
| Deploy (GitHub) | **success**, 1m 10s, 11 langkah |
| Rilis APK (GitHub) | **success**, 5m 30s, aset 19,3 MB |

## Bukti pipeline menangkap kesalahan

Aturan "potongan kasbon tidak boleh melebihi sisa utang" sengaja dihapus, lalu pengujian dijalankan:

```
FAILED  Tests\Feature\PayrollCalculationTest > potongan kasbon melebihi sisa ditolak
  ➜  122▕  ->assertStatus(422)
  Tests:    1 failed, 52 passed (157 assertions)
```

Setelah kode dikembalikan: `53 passed (158 assertions)`. Artinya kesalahan logika penggajian
tertahan sebelum masuk ke branch utama.

## Menjalankan pipeline secara manual

GitHub → **Actions** → pilih workflow → **Run workflow**.
Untuk membuat rilis: `git tag v1.0.1 && git push origin v1.0.1`.
