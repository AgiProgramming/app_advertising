# Aplikasi Mobile

Aplikasi Flutter untuk tiga peran sekaligus. Kode: repositori
[`app_advertising_mobile`](https://github.com/AgiProgramming/app_advertising_mobile).

## Layar per peran

### Admin

| Layar | Fungsi |
|---|---|
| Beranda | total SPK, karyawan aktif, kasbon berjalan, slip belum dibayar, panel "Perlu tindakan" |
| Slip | daftar + filter status/tahun, buka detail, buat slip baru |
| Detail slip | struk lengkap (PPN, komisi, tunjangan, kasbon, gaji bersih), QR, Bagikan, Unduh PDF |
| SPK | daftar + filter, form tambah/ubah |
| Kasbon | daftar kasbon, form catat kasbon |
| Data master | tab Karyawan & Toko, form tambah karyawan (sekaligus akun login) |
| Notifikasi | riwayat notifikasi + tandai dibaca |
| Profil | identitas, menu, keluar |

### Karyawan

Beranda (gaji diterima / tertunda / sisa kasbon, tugas aktif), Slip (hanya miliknya),
Detail slip, **Tugas** (SPK yang ditugaskan), Profil (menu "Kasbon saya").

### Owner

Beranda ringkasan bengkel: margin operasional, omzet, jumlah SPK, gaji terbayar, toko teratas.

## Konfigurasi alamat API

`lib/core/api_config.dart` menentukan base URL:

```dart
static const String _override = String.fromEnvironment('API_BASE_URL');

// urutan: override → khusus emulator → localhost
```

| Kondisi | Base URL |
|---|---|
| `--dart-define=API_BASE_URL=...` | nilai yang diberikan |
| Emulator Android (tanpa override) | `http://10.0.2.2:8000` |
| Lainnya | `http://127.0.0.1:8000` |

!!! danger "Jangan lupa `--dart-define` saat build rilis"
    Tanpa `--dart-define`, APK akan menembak `10.0.2.2` (alamat khusus emulator) sehingga
    **tidak bisa dipakai di HP sungguhan**. Pipeline `rilis.yml` sudah menyetel nilai ini ke
    `https://apimann.gondrii.my.id` dan bisa ditimpa lewat repository variable `API_BASE_URL`.

## Build & pasang

=== "Emulator (debug)"

    ```bash
    flutter run
    ```

=== "HP fisik (rilis)"

    ```bash
    flutter build apk --release --target-platform android-arm64 \
      --dart-define=API_BASE_URL=https://apimann.gondrii.my.id
    ```

    Hasil: `build/app/outputs/flutter-apk/app-release.apk` (±19,3 MB).

=== "Ambil dari GitHub Release"

    Unduh `mann-plus-payroll-v1.0.0.apk` di halaman
    [Release](https://github.com/AgiProgramming/app_advertising_mobile/releases), lalu pasang
    (aktifkan "Install unknown apps" untuk aplikasi pengelola berkas).

## Notifikasi

1. Saat aplikasi dibuka, `Firebase.initializeApp()` dijalankan dalam `try/catch` — bila konfigurasi
   belum ada, aplikasi tetap berjalan (hanya fitur push yang mati).
2. Setelah login, `PushService` meminta izin notifikasi (Android 13+) lalu mengirim token FCM ke
   `POST /api/device-token`.
3. Notifikasi masuk ditampilkan pada channel **`payroll`** dan, bila aplikasi aktif, juga muncul
   sebagai notifikasi lokal.
4. Menekan notifikasi membuka layar notifikasi (dan slip terkait bila ada `payroll_id`).
5. Saat logout, token perangkat dihapus lewat `DELETE /api/device-token`.

Berkas konfigurasi yang harus ada:

| Berkas | Lokasi | Isi |
|---|---|---|
| `google-services.json` | `android/app/` | konfigurasi Firebase untuk aplikasi Android |
| service account JSON | server: `storage/app/firebase/service-account.json` | kredensial pengirim FCM |

## Pengujian

```bash
flutter analyze     # 0 issue
flutter test        # 8 pengujian widget/unit
```

Uji integrasi ke API nyata (butuh backend hidup + data):

```bash
flutter test test/admin_crud_integration_test.dart --run-skipped
```

## Catatan teknis

- **State management:** `provider` + `ChangeNotifier`. `NotificationState` sengaja diletakkan
  **di atas `MaterialApp`** agar layar yang dibuka lewat `Navigator.push` tetap bisa membacanya
  (dulu menyebabkan `ProviderNotFoundException`).
- **Format angka/tanggal:** `intl` dengan locale `id_ID`; rupiah memakai pemisah ribuan titik dan
  desimal koma (`1.463.763,00`).
- **Font:** Instrument Sans disertakan lokal agar tampilan tidak bergantung koneksi internet.
- **Signing:** APK rilis memakai debug signing (bawaan template Flutter). Untuk distribusi
  Play Store, buat keystore sendiri dan tambahkan `key.properties`.
