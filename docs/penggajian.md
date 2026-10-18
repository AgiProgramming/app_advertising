# Aturan Perhitungan Penggajian

Seluruh perhitungan berada di `app/Services/PayrollService.php` dan dipakai bersama oleh
**pratinjau** (`/api/payrolls/preview`) dan **penyimpanan slip** (`POST /api/payrolls`),
sehingga angka yang dilihat admin sebelum menyimpan sama dengan angka yang tersimpan.

## Rumus

```
1. net_spk_value      = spk_value − (spk_value × 3%)          # PPN 3%
2. komisi             = net_spk_value × salary_percentage%    # default 22%
3. gaji_kotor         = komisi + meal_allowance
4. potongan_kasbon    = min(sisa_kasbon, gaji_kotor − meal_allowance)
5. net_salary         = max(0, gaji_kotor − potongan_kasbon)
```

Keterangan:

| Istilah | Arti |
|---|---|
| `spk_value` | nilai pekerjaan pada SPK |
| **PPN 3%** | potongan pajak yang dibebankan atas nilai SPK |
| **komisi 22%** | bagian karyawan dari nilai bersih SPK (persentase bisa diubah admin) |
| `meal_allowance` | tunjangan makan (opsional, diisi admin) |
| `sisa_kasbon` | total kasbon karyawan dikurangi yang sudah pernah dipotong |
| `net_salary` | **gaji bersih** yang muncul di slip |

## Batas pengaman

!!! danger "Potongan kasbon tidak boleh melebihi utang"
    Nilai `cash_advance_deduction` yang dikirim klien **ditolak (422)** bila lebih besar dari sisa
    kasbon karyawan. Bila admin tidak mengisi, sistem memotong otomatis sebesar
    `min(sisa_kasbon, gaji_kotor − meal_allowance)` sehingga gaji tidak pernah minus.

Aturan lain:

- `meal_allowance` **tidak boleh melebihi gaji kotor** (ditolak 422).
- `period` wajib berformat `Y-m` (contoh `2026-10`).
- Satu SPK hanya boleh punya **satu slip**.
- `net_salary` tidak pernah negatif (`max(0, ...)`).

## Contoh perhitungan nyata

### Contoh 1 — slip dengan kasbon (data uji)

| Komponen | Nilai |
|---|---|
| Nilai SPK | 1.463.763,00 |
| PPN 3% | −43.912,89 |
| Nilai bersih | 1.419.850,11 |
| Komisi 22% | 312.367,02 |
| Tunjangan makan | 100.000,00 |
| Potongan kasbon | 200.000,00 |
| **Gaji bersih** | **212.367,02** |

Perhitungan: `1.463.763 × 3% = 43.912,89` → `1.463.763 − 43.912,89 = 1.419.850,11`
→ `1.419.850,11 × 22% = 312.367,02` → `312.367,02 + 100.000 − 200.000 = 212.367,02` ✔

### Contoh 2 — pratinjau di aplikasi

| Komponen | Nilai |
|---|---|
| Nilai SPK | 5.000.000,00 |
| PPN 3% | 150.000,00 |
| Nilai bersih | 4.850.000,00 |
| Komisi 22% | 1.067.000,00 |
| **Gaji bersih** | **1.067.000,00** |

### Contoh 3 — slip produksi (pengujian notifikasi)

SPK `2.000.000` → PPN 3% `60.000` → nilai bersih `1.940.000` → komisi 22% = **426.800,00**
(angka ini yang muncul pada notifikasi push "Gaji bersih Rp 426.800,00").

## Mengapa PPN dulu, baru komisi?

Nilai SPK adalah nilai kontrak dengan toko. Pajak (PPN) mengurangi nilai kontrak, sehingga bagian
karyawan dihitung dari nilai **bersih** setelah pajak — bukan dari nilai kotor. Urutan ini
disepakati dengan pemilik bengkel dan dijaga oleh pengujian otomatis
(`tests/Feature/PayrollCalculationTest.php`).

## Kasbon

- Kasbon dicatat per karyawan (`cash_advances`), tidak otomatis memotong gaji saat dibuat.
- Pemotongan terjadi saat slip dibuat: admin boleh mengisi nominal, atau membiarkan sistem
  memotong maksimal sebesar sisa utang.
- Sisa kasbon = `total kasbon − total yang sudah dipotong`, tidak pernah negatif.

## Pengujian yang menjaga aturan ini

| Nama uji | Yang dijaga |
|---|---|
| `test_perhitungan_gaji_sesuai_rumus` | urutan PPN → komisi → tunjangan |
| `test_potongan_kasbon_melebihi_sisa_ditolak` | batas potongan kasbon (422) |
| `test_tunjangan_makan_melebihi_gaji_kotor_ditolak` | tunjangan tidak melebihi gaji kotor |
| `test_slip_dihapus_mengembalikan_status_spk` | rollback status SPK |

Uji-uji ini yang terbukti **menangkap kesalahan** saat dilakukan demo regresi: menghapus aturan
batas kasbon membuat 1 uji gagal (`1 failed, 52 passed`) sebelum kode dikembalikan.
