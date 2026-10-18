# Dokumentasi Mann Plus Payroll

Sistem penggajian karyawan untuk **Bengkel Mann Plus Advertising** (Neon Box · Billboard · Media
Promosi). Terdiri dari **REST API** (Laravel) dan **aplikasi mobile** (Flutter) yang dipakai
karyawan, admin, dan pemilik bengkel.

Dokumentasi ini menjelaskan sistem dari sisi teknis maupun operasional: teknologi yang dipakai,
struktur data, daftar endpoint, aturan perhitungan gaji, cara deploy, pipeline CI/CD, hingga
catatan masalah yang pernah muncul beserta solusinya.

---

## Sekilas sistem

| Bagian | Keterangan |
|---|---|
| **API** | `https://apimann.gondrii.my.id` — Laravel 12, PHP 8.3, MySQL 8 |
| **Aplikasi mobile** | Flutter 3.41, APK rilis tersedia di halaman Release |
| **Repositori** | [`app_advertising`](https://github.com/AgiProgramming/app_advertising) · [`app_advertising_mobile`](https://github.com/AgiProgramming/app_advertising_mobile) |
| **Hosting** | Shared hosting cPanel (tanpa SSH), PHP 8.3, MySQL 8 |
| **Pipeline** | GitHub Actions — CI (uji otomatis), CD (deploy FTPS), rilis APK |

## Siapa memakai apa

| Peran | Yang bisa dilakukan |
|---|---|
| **Karyawan** | Melihat slip gaji sendiri, menerima notifikasi slip, melihat daftar tugas (SPK), melihat sisa kasbon |
| **Admin** | Mengelola SPK, karyawan, toko, kasbon; membuat slip gaji; menandai slip dibayar |
| **Owner** | Melihat ringkasan bengkel: omzet, margin operasional, gaji terbayar, toko teratas |

## Alur inti penggajian

```
SPK dibuat (nilai pekerjaan)
      │
      ▼
Hitung gaji:  PPN 3% → nilai bersih → komisi 22% + tunjangan − potongan kasbon
      │
      ▼
Slip tersimpan  ──►  notifikasi push + riwayat in-app ke karyawan
      │
      ▼
Slip dibayar  ──►  status SPK ikut berubah, notifikasi kedua terkirim
```

## Mulai cepat

=== "Mencoba API"

    ```bash
    curl -X POST https://apimann.gondrii.my.id/api/login \
      -H "Content-Type: application/json" \
      -H "Accept: application/json" \
      -d '{"username":"admin_utama","password":"<password>"}'
    ```

    Balasan berisi `access_token` yang dipakai pada header
    `Authorization: Bearer <token>` untuk endpoint berikutnya.

=== "Menjalankan di komputer"

    ```bash
    # backend
    cd app_advertising
    composer install
    cp .env.example .env && php artisan key:generate
    php artisan migrate --seed
    php artisan serve

    # mobile (emulator Android otomatis memakai 10.0.2.2:8000)
    cd app_advertising_mobile
    flutter pub get
    flutter run
    ```

## Peta dokumentasi

| Halaman | Isi |
|---|---|
| [Teknologi](teknologi.md) | Daftar teknologi, versi, dan alasan pemilihannya |
| [Arsitektur & Data](arsitektur.md) | Alur sistem, struktur folder, skema database |
| [Referensi API](api.md) | Semua endpoint, hak akses, contoh permintaan & balasan |
| [Aturan Penggajian](penggajian.md) | Rumus perhitungan, batas kasbon, contoh angka nyata |
| [Aplikasi Mobile](mobile.md) | Layar per peran, konfigurasi API, build APK, notifikasi |
| [Deployment](deployment.md) | Pemasangan di hosting cPanel tanpa SSH |
| [CI/CD](ci-cd.md) | Empat pipeline, pemicu, secret, hasil terukur |
| [Keamanan](keamanan.md) | Temuan audit dan perbaikannya |
| [Pengujian](pengujian.md) | Cara menjalankan uji, cakupan, bukti regresi |
| [Troubleshooting](troubleshooting.md) | Masalah nyata yang pernah muncul + solusi |
| [Lampiran](lampiran.md) | Daftar screenshot dan angka ringkasan |

---

!!! info "Konteks akademik"
    Sistem ini dikembangkan sebagai bagian skripsi *"Analisis dan Optimalisasi Fitur Penggajian
    Karyawan Menggunakan Pendekatan DevOps"*. Praktik DevOps yang diterapkan: pengelolaan versi
    kode (Git), pengujian otomatis (PHPUnit & Flutter test), serta integrasi/penerapan
    berkelanjutan (GitHub Actions).
