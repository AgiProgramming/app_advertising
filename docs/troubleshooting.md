# Troubleshooting

Kumpulan masalah **nyata** yang muncul selama pengembangan dan pemasangan, beserta penyebab dan
solusinya. Bagian ini sering menyelamatkan waktu ketika terjadi hal serupa lagi.

## Pemasangan di hosting

??? question "Aplikasi blank / 500 setelah diunggah"
    **Penyebab umum:** versi PHP masih 7.4 (Laravel 12 butuh ≥ 8.2), atau ekstensi tidak lengkap.
    **Cek:** buat `__cek.php` sementara berisi `phpversion()` dan `extension_loaded()`.
    **Solusi:** cPanel → MultiPHP Manager → pilih **`ea-php83`**.

??? question "`alt-php83` aktif tapi intl/zip/gd/pdo_mysql kosong"
    **Penyebab:** di CloudLinux, `alt-php*` perlu ekstensi diaktifkan satu per satu.
    **Solusi:** pakai `ea-php83` yang sudah lengkap.

??? question "Versi PHP kembali ke 7.4 sendiri"
    **Penyebab:** cPanel me-reset pengaturan vhost (pernah terjadi setelah rebuild).
    **Solusi:** set ulang di MultiPHP Manager; bila berulang, tambahkan handler PHP di `.htaccess`.

??? question "`Call to undefined function exec()`"
    **Penyebab:** `exec`/`shell_exec` dimatikan di shared hosting.
    **Solusi:** ganti pembersihan folder dengan PHP murni (RecursiveDirectoryIterator + `rmdir`/`unlink`).

??? question "`The name “x” does not begin with the required prefix`"
    **Penyebab:** pembuatan database/user lewat API cPanel wajib memakai prefix akun.
    **Solusi:** gunakan nama lengkap, contoh `gonn2842_payroll`.

??? question "Upload lewat API cPanel gagal: `file already exists`"
    **Solusi:** sertakan parameter `overwrite=1` pada `Fileman::upload_files`.

??? question "`Fileman::mkdir` tidak ditemukan"
    **Penyebab:** modul UAPI di server ini tidak menyediakan `mkdir`.
    **Solusi:** buat folder lewat skrip PHP sekali pakai (`mkdir(..., 0775, true)`).

## Deploy & pipeline

??? question "Deploy gagal di langkah FTPS: `InvalidAuthenticationInfo`"
    **Penyebab:** `FTP_HOST` menunjuk ke nama yang sertifikat TLS-nya tidak cocok
    (server membalas galat gaya HTTP, bukan FTP).
    **Cek:** `openssl s_client -connect <host>:21 -starttls ftp` → lihat `subject`.
    **Solusi:** pakai nama yang tertera di sertifikat, contoh `manaslu.iixcp.rumahweb.net`.

??? question "`SEC_E_WRONG_PRINCIPAL` saat FTPS"
    Sama seperti di atas — nama host tidak cocok dengan sertifikat.

??? question "Job deploy tidak pernah jalan (di-skip)"
    **Penyebab:** repository variable `DEPLOY_ENABLED` belum bernilai `true`.
    Job-level `if` **tidak bisa** membaca `secrets.*`, jadi sakelarnya memakai variable.

??? question "Deploy sukses tapi halaman tetap 500"
    **Cek berurutan:**
    1. `~/app/.env` ada dan `APP_KEY` terisi
    2. `~/app/vendor/` ada (hasil `composer install`)
    3. `storage/` dan `bootstrap/cache/` dapat ditulis
    4. `https://domain/up` → harus 200

??? question "`app:init-admin` tidak membuat akun setelah deploy kedua"
    **Penyebab:** `env()` mengembalikan `null` setelah `config:cache`.
    **Solusi:** perintah membaca `config('app.init_admin_password')` (sudah diperbaiki), dan
    `optimize` dijalankan **setelah** perintah tersebut.

## Aplikasi mobile

??? question "APK rilis tidak bisa login di HP (connection refused)"
    **Penyebab:** build tanpa `--dart-define`, sehingga memakai `http://10.0.2.2:8000`
    (alamat khusus emulator).
    **Cek:** `grep -a "apimann.gondrii.my.id" app-release.apk` harus menemukan hasil.
    **Solusi:** build ulang dengan `--dart-define=API_BASE_URL=https://apimann.gondrii.my.id`.

??? question "`ProviderNotFoundException: NotificationState`"
    **Penyebab:** provider diletakkan di dalam `MaterialApp.home`, sehingga rute hasil
    `Navigator.push` tidak bisa membacanya.
    **Solusi:** pindahkan ke `MultiProvider` **di atas** `MaterialApp` (sudah diperbaiki + ada
    test regresinya).

??? question "Build APK gagal: `requires core library desugaring`"
    **Solusi:** aktifkan `isCoreLibraryDesugaringEnabled = true` dan tambahkan
    `coreLibraryDesugaring("com.android.tools:desugar_jdk_libs:2.1.4")` di `android/app/build.gradle.kts`.

??? question "Notifikasi tidak masuk di perangkat"
    **Cek berurutan:**
    1. `google-services.json` ada di `android/app/`
    2. Izin notifikasi diberikan (Android 13+)
    3. Token terdaftar: tabel `device_tokens` berisi baris perangkat
    4. Di server: `storage/app/firebase/service-account.json` ada dan valid
    5. `FIREBASE_PROJECT_ID` sama dengan `project_id` pada service account

## Pengembangan

??? question "Test gagal: `could not find driver` (SQLite)"
    **Penyebab:** ekstensi `pdo_sqlite` tidak aktif di PHP lokal.
    **Solusi:** aktifkan di `php.ini`, atau jalankan test dengan MySQL (konfigurasi sekarang).

??? question "Token Sanctum gagal tersimpan di MySQL"
    **Penyebab:** kolom `personal_access_tokens.tokenable_id` bertipe `bigint`, sedangkan `users.id` UUID.
    **Solusi:** migrasi diubah memakai `uuidMorphs` (sudah diperbaiki).

??? question "`pint --test` gagal pada satu berkas yang merasa sudah rapi"
    **Penyebab:** `git checkout -- <file>` mengembalikan berkas ke versi **HEAD** (pra-format),
    bukan ke versi yang sudah diformat.
    **Solusi:** jalankan `vendor/bin/pint` lagi, lalu `--test`, baru commit.

??? question "Uji logout tetap 200 setelah token dicabut"
    **Penyebab:** guard Sanctum di-cache per proses saat test.
    **Solusi:** helper `asToken()`/`asGuest()` memanggil `$this->app['auth']->forgetGuards()`.

## Git & GitHub

??? question "Push ditolak `403` padahal sudah login"
    **Penyebab:** Credential Manager masih menyimpan akun lain.
    **Solusi:** `git credential-manager github logout`, lalu push ulang memakai akun yang benar.

??? question "Commit tercatat atas nama orang lain"
    **Penyebab:** `user.name`/`user.email` global dipakai semua repo.
    **Solusi:** set **per repo** (`git config user.name ...` tanpa `--global`). Untuk mengubah
    riwayat yang sudah ada: `git filter-branch --env-filter` (tanggal commit tetap dipertahankan).

??? question "Push pertama ditolak `fetch first`"
    **Penyebab:** repo di GitHub sudah punya commit awal (mis. README).
    **Solusi:** `git pull --rebase origin main` lalu push.
