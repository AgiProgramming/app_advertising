# Aturan Perhitungan Penggajian

Perhitungan mengikuti cara kerja bengkel yang sebenarnya: **satu SPK dikerjakan beberapa orang**,
seorang karyawan **boleh mengambil beberapa SPK**, dan bila ada yang tidak masuk kerja,
**orang pengganti** mengambil hari kerja miliknya.

Seluruh perhitungan berada di `app/Services/PayrollService.php` dan dipakai bersama oleh
**pratinjau** (`/api/payrolls/preview`) dan **penyimpanan slip** (`POST /api/payrolls`),
sehingga angka yang dilihat admin sebelum menyimpan sama dengan angka yang tersimpan.

## Struktur data

| Tabel | Isi |
|---|---|
| `spks` | dokumen SPK dari klien: nomor, nilai, tanggal mulai/selesai, durasi |
| `spk_employees` | penugasan: siapa mengerjakan SPK apa, peran `worker`/`replacement`, berapa hari masuk |
| `payrolls` | satu slip = satu karyawan untuk satu periode |
| `payroll_items` | rincian bagian karyawan dari tiap SPK di dalam slip |

`duration_days` dihitung otomatis dari `start_date` sampai `end_date` (inklusif).

## Rumus

### 1. Bagian dari satu SPK

```
nilai_harian = nilai_SPK ÷ (jumlah_pekerja × jumlah_hari_SPK)
bagian       = nilai_harian × hari_karyawan_masuk
```

`jumlah_pekerja` hanya menghitung peran `worker` — **pengganti tidak menambah pembagi**,
karena pengganti mengambil hari milik orang yang digantikan.

Contoh: SPK Rp7.500.000, 3 hari, 5 pekerja → nilai harian = 7.500.000 ÷ 15 = **Rp500.000**.

| Orang | Peran | Hari masuk | Bagian |
|---|---|---|---|
| Pekerja 1 | worker | 1 | Rp500.000 |
| Pengganti | replacement | 2 | Rp1.000.000 |
| Pekerja 2–5 | worker | 3 | Rp1.500.000 masing-masing |

Total kelima slot tetap Rp7.500.000.

### 2. Bagian kotor satu karyawan

```
bagian_kotor = Σ bagian dari semua SPK pada periode itu
```

### 3. Potongan

```
PPN          = 4%  × bagian_kotor
komisi       = 22% × (bagian_kotor − PPN)      # milik Mann Plus
uang_makan   = Rp100.000 × jumlah SPK yang diikuti
kasbon       = potongan utang karyawan (opsional)
```

```
gaji_bersih  = bagian_kotor − PPN − komisi − uang_makan − kasbon
```

**Gaji bersih boleh minus.** Bila potongan melebihi bagian karyawan, slip menampilkan nilai
minus apa adanya (tidak dipotong menjadi nol), dan aplikasi menandainya `is_negative`.

### 4. Tarif yang bisa diubah

| Konfigurasi | Default | Variabel `.env` |
|---|---|---|
| `payroll.ppn_rate` | 4% | `PAYROLL_PPN_RATE` |
| `payroll.commission_rate` | 22% | `PAYROLL_COMMISSION_RATE` |
| `payroll.meal_rate` | Rp100.000 | `PAYROLL_MEAL_RATE` |

Tarif yang dipakai **disimpan di setiap slip** (`ppn_rate`, `commission_rate`, `meal_rate`),
sehingga slip lama tetap akurat walau tarif diubah kemudian.

## Contoh perhitungan nyata

### Contoh 1 — dua SPK, satu orang (contoh pemilik bengkel)

Karyawan A ikut SPK A (nilai Rp2.500.000, 4 orang) dan SPK B (nilai Rp2.500.000, 3 orang).

| Tahap | Perhitungan | Hasil |
|---|---|---|
| Bagian SPK A | 2.500.000 ÷ 4 | 625.000,00 |
| Bagian SPK B | 2.500.000 ÷ 3 | 833.333,33 |
| **Bagian kotor** | | **1.458.333,33** |
| PPN 4% | × 4% | −58.333,33 |
| Setelah PPN | | 1.400.000,00 |
| Komisi 22% | × 22% | −308.000,00 |
| Setelah komisi | | 1.092.000,00 |
| Uang makan | 2 SPK × 100.000 | −200.000,00 |
| **Gaji bersih** | | **892.000,00** |

### Contoh 2 — pekerja yang hanya masuk sehari

SPK Rp7.500.000, 3 hari, 5 pekerja. Pekerja 1 hanya masuk hari pertama, pengganti masuk hari kedua
dan ketiga.

| | Pekerja 1 | Pengganti |
|---|---|---|
| Bagian kotor | 500.000,00 | 1.000.000,00 |
| PPN 4% | −20.000,00 | −40.000,00 |
| Komisi 22% | −105.600,00 | −211.200,00 |
| Uang makan | −100.000,00 | −100.000,00 |
| **Gaji bersih** | **274.400,00** | **648.800,00** |

### Contoh 3 — hasil minus

SPK Rp500.000 dikerjakan 5 orang, masing-masing 1 hari. Bagian per orang Rp100.000.

```
100.000 − 4.000 (PPN) = 96.000
96.000 − 21.120 (komisi) = 74.880
74.880 − 100.000 (uang makan) = −25.120
```

Slip menampilkan **−Rp25.120,00** dan diberi keterangan bahwa potongan melebihi bagian karyawan.

## Batas pengaman

!!! danger "Potongan kasbon tidak boleh melebihi utang"
    Nilai `cash_advance_deduction` yang dikirim klien **ditolak (422)** bila lebih besar dari sisa
    kasbon karyawan. Bila admin tidak mengisi, sistem memotong otomatis sebesar
    `min(sisa_kasbon, bagian setelah komisi − uang makan)`.

Aturan lain:

- `days_worked` tidak boleh melebihi `duration_days` (otomatis dipotong saat disimpan).
- `end_date` tidak boleh sebelum `start_date` (ditolak 422).
- `period` wajib berformat `Y-m` (contoh `2026-10`).
- Satu karyawan hanya boleh punya **satu slip per periode**.
- SPK hanya masuk hitungan bila tanggal SPK berada di periode tersebut.
- Karyawan dengan `days_worked = 0` tidak dihitung (mis. absen penuh tanpa pengganti).

## Alur pemakaian

1. Admin membuat SPK: nilai, tanggal mulai/selesai, lalu memilih anggotanya
   (pekerja + hari masuk, dan pengganti bila ada).
2. Admin membuka **Pratinjau gaji**: pilih karyawan dan periode → rincian tiap SPK tampil.
3. Simpan slip untuk satu karyawan, atau **buat sekaligus** untuk semua karyawan pada periode itu
   (`POST /api/payrolls/generate`).
4. Slip dapat diunduh sebagai PDF, diverifikasi lewat QR, dan karyawan menerima notifikasi.

## Pengujian yang menjaga aturan ini

`tests/Feature/PayrollCalculationTest.php` (12 kasus) dan `tests/Feature/SpkMemberTest.php` (5 kasus)
mengunci perilaku berikut:

- pembagian per hari kerja dan peran pengganti
- contoh dua SPK di atas menghasilkan tepat **892.000**
- uang makan dipotong per SPK, bukan per slip
- hasil minus tetap minus
- satu slip per karyawan per periode
- pembuatan slip massal melewati karyawan yang sudah punya slip
- kasbon tidak boleh melebihi sisa utang
- aplikasi versi lama (`spk_id` saja) masih didukung

Jalankan dengan:

```bash
php artisan test --filter="PayrollCalculationTest|SpkMemberTest"
```
