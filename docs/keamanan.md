# Keamanan

Bagian ini merangkum audit keamanan API (90 skenario permintaan) beserta perbaikannya.
Laporan lengkap: `LAPORAN_AUDIT_API.md`.

## Temuan kritis & tinggi

| # | Temuan | Dampak | Perbaikan |
|---|---|---|---|
| 1 | **Backdoor login Owner** — username khusus (`dollop`, `owner`, `bapak dollop`) dengan password tetap `dollop123` | siapa pun bisa masuk sebagai Owner | dihapus total; login hanya lewat tabel `users` |
| 2 | **Tanpa pembatasan percobaan login** | brute force tak terbatas | `RateLimiter` `login`: 5/menit per username+IP, 20/menit per IP |
| 3 | **`APP_DEBUG=true`** | stack trace 859 KB terbuka (path, query, kredensial) | `APP_DEBUG=false` di produksi; penanganan error dipaksa JSON |
| 4 | **Potongan kasbon tak dibatasi** | gaji bisa minus / kasbon dipotong melebihi utang | validasi 422 + `min(sisa_kasbon, gaji − tunjangan)` |
| 5 | **Status SPK tidak rollback** saat slip dihapus | data status pekerjaan salah | `previous_job_status` disimpan & dikembalikan |
| 6 | **Akun nonaktif tetap bisa login** (token lama masih sah) | mantan karyawan tetap punya akses | `RoleMiddleware` menolak karyawan nonaktif meski token valid |
| 7 | **Request tanpa header `Accept` → 500** | informasi internal bocor | `shouldRenderJsonWhen` + `redirectGuestsTo(null)` untuk `api/*` |
| 8 | **404 membocorkan nama model** | enumerasi struktur internal | pesan generik `Data tidak ditemukan.` / `Endpoint tidak ditemukan.` |

## Temuan sedang

| Temuan | Perbaikan |
|---|---|
| Token Sanctum tanpa masa berlaku | kedaluwarsa **7 hari**, dicabut saat logout |
| Pesan login membedakan "username tidak ada" vs "password salah" | pesan tunggal: `Username atau password salah.` |
| Validasi input longgar (panjang teks, format periode) | batas panjang + `period` wajib `Y-m` |
| Tidak ada header keamanan | middleware `SecurityHeaders`: `X-Content-Type-Options`, `X-Frame-Options: DENY`, `Referrer-Policy: no-referrer`, `Permissions-Policy`; `X-Powered-By` dihapus |
| Update karyawan selalu 422 | password opsional saat mengubah data |
| Tunjangan bisa melebihi gaji kotor | validasi 422 |

## Praktik keamanan yang berlaku sekarang

- **Autentikasi:** Sanctum bearer token, kedaluwarsa 7 hari, dihapus saat logout.
- **Otorisasi:** middleware `role:` per grup rute; karyawan hanya bisa mengakses data miliknya
  (dicek di controller, termasuk unduh PDF slip).
- **Anti-enumerasi:** pesan login & 404 generik.
- **Rate limit:** login (5/menit per username+IP, 20/menit per IP).
- **Transport:** HTTPS dengan sertifikat Let's Encrypt; deploy memakai FTPS.
- **Rahasia tidak masuk repo:** `.env`, service account Firebase, dan token deploy berada di
  server/secret GitHub, tidak pernah di dalam `release.zip`.
- **Endpoint deploy:** `deploy.php` dilindungi token acak 48 karakter, memakai `hash_equals`,
  dan menghapus dirinya sendiri setelah selesai (versi bootstrap).

## Yang belum diterapkan

| Area | Catatan |
|---|---|
| **2FA untuk admin** | belum ada; disarankan untuk versi berikutnya |
| **Rotasi kunci otomatis** | `APP_KEY` dan token deploy dirotasi manual |
| **Audit log perubahan data** | belum ada tabel riwayat perubahan |
| **Pembatasan IP endpoint deploy** | hanya mengandalkan token (IP runner GitHub berubah-ubah) |
| **Signing APK produksi** | masih debug signing; belum ada keystore resmi |

## Daftar periksa saat mengoperasikan

- [ ] `APP_DEBUG=false`
- [ ] password admin bawaan sudah diganti
- [ ] `~/deploy/admin-password.txt` dihapus setelah dipakai
- [ ] `~/deploy/token.txt` dirahasiakan, dirotasi bila dicurigai bocor
- [ ] berkas `bootstrap.php` / `artisan.php` dihapus setelah dipakai
- [ ] token GitHub (PAT) yang dipakai menyiapkan secret sudah **direvoke**
- [ ] kredensial Firebase dibatasi (batasi API key ke package name + SHA-1 aplikasi)
