# Pengujian

## Ringkasan

| Lapisan | Alat | Jumlah | Waktu |
|---|---|---|---|
| Backend (unit + feature) | PHPUnit via `php artisan test` | **53 test / 158 assertion** | ±3,4 detik |
| Aplikasi mobile | `flutter test` | **8 test** | ±3 detik |
| Analisis statis mobile | `flutter analyze` | 0 issue | ±4 detik |
| Standar format backend | Laravel Pint | 0 pelanggaran | ±1 detik |
| Uji integrasi mobile (manual) | `flutter test --run-skipped` | 2 skenario CRUD & login | ±30 detik |

## Menjalankan

=== "Backend"

    ```bash
    cd app_advertising
    php artisan test                    # seluruh pengujian
    php artisan test --filter=Payroll   # hanya yang berkaitan payroll
    vendor/bin/pint --test              # cek format
    ```

    Pengujian memakai database **`app_advertising_test`** (lihat `phpunit.xml`).

=== "Mobile"

    ```bash
    cd app_advertising_mobile
    flutter analyze
    flutter test
    ```

=== "Uji integrasi (butuh API hidup)"

    ```bash
    # terminal 1: php artisan serve
    flutter test test/admin_crud_integration_test.dart --run-skipped
    ```

    Skenario: login admin → buat toko → karyawan → SPK → pratinjau gaji → simpan slip →
    tandai dibayar (SPK ikut berubah) → login sebagai karyawan → slip terlihat → hapus slip
    (status SPK rollback) → bersihkan data.

## Apa saja yang diuji (backend)

| Berkas | Cakupan |
|---|---|
| `tests/Feature/AuthLoginTest.php` | login benar/salah, akun nonaktif ditolak, pesan generik, rate limit, logout mencabut token |
| `tests/Feature/RoleAccessTest.php` | Admin/Owner/Karyawan hanya bisa mengakses rutenya; karyawan tidak bisa mengunduh slip orang lain |
| `tests/Feature/PayrollCalculationTest.php` | rumus PPN → komisi → tunjangan → kasbon; batas potongan kasbon; tunjangan > gaji ditolak; rollback status SPK |
| `tests/Feature/SlipDocumentTest.php` | PDF slip dihasilkan (`%PDF`), QR/verifikasi, PDF tanpa login ditolak |
| `tests/Feature/NotificationTest.php` | feed notifikasi, tandai dibaca, registrasi device token, FCM dipanggil dengan payload benar (HTTP di-fake) |
| `tests/Feature/InitAdminCommandTest.php` | `app:init-admin` idempoten, tidak membuat akun bila password awal kosong |
| `tests/Unit/*` | perhitungan sisa kasbon, format nomor slip |

## Apa saja yang diuji (mobile)

| Berkas | Cakupan |
|---|---|
| `test/formatters_test.dart` | format rupiah (`1.463.763,00`), tanggal, periode aman untuk nilai kosong |
| `test/login_screen_test.dart` | layar login muat penuh tanpa overflow; validasi field kosong |
| `test/notification_navigation_test.dart` | layar Notifikasi bisa dibuka lewat `Navigator.push` (regresi `ProviderNotFoundException`) |
| `test/api_integration_test.dart`, `test/admin_crud_integration_test.dart` | uji integrasi (di-skip pada run biasa) |

## Bukti pengujian menangkap kesalahan

Demo regresi: aturan batas potongan kasbon dihapus dari `PayrollService`, lalu dijalankan:

```
FAILED  Tests\Feature\PayrollCalculationTest > potongan kasbon melebihi sisa ditolak
  ➜  122▕  ->assertStatus(422)
  Tests:    1 failed, 52 passed (157 assertions)
```

Kode dikembalikan → `53 passed (158 assertions)`.

## Catatan

- Uji integrasi Flutter **tidak** dijalankan di pipeline karena memerlukan API hidup dan data;
  keduanya ditandai `skip` dan dijalankan manual.
- Test FCM memakai service account palsu (`tests/fixtures/`) + `Http::fake()`, sehingga tidak
  memerlukan kredensial asli maupun koneksi internet.
- Pengujian backend memakai `RefreshDatabase`, jadi database uji selalu bersih setiap kali jalan.
