<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesDomainData;
use Tests\TestCase;

/**
 * Pengujian perhitungan gaji model baru (SPK banyak orang + pengganti).
 *
 * Rumus yang diuji (lihat PayrollService::calculateForEmployee):
 *   nilai harian  = nilai SPK / (jumlah pekerja x jumlah hari SPK)
 *   bagian karyawan = nilai harian x hari dia masuk
 *   bagian kotor  = jumlah bagian dari semua SPK pada periode itu
 *   PPN           = 4% x bagian kotor
 *   komisi        = 22% x (bagian kotor - PPN)   -> milik Mann Plus
 *   uang makan    = Rp100.000 x jumlah SPK yang diikuti
 *   gaji bersih   = bagian kotor - PPN - komisi - uang makan - kasbon
 */
class PayrollCalculationTest extends TestCase
{
    use CreatesDomainData;
    use RefreshDatabase;

    private function auth(string $token): static
    {
        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    private function adminToken(): string
    {
        return $this->tokenFor($this->admin());
    }

    private function preview(string $employeeId, string $period = '2026-10', array $extra = []): array
    {
        return $this->auth($this->adminToken())
            ->postJson('/api/payrolls/preview', array_merge([
                'employee_id' => $employeeId,
                'period' => $period,
            ], $extra))
            ->assertOk()
            ->json('data');
    }

    /**
     * SPK 3 hari dikerjakan 5 orang. Pekerja hanya masuk 1 hari,
     * pengganti masuk 2 hari -> upah dibagi menurut hari kerja.
     */
    public function test_bagian_dihitung_berdasarkan_hari_kerja(): void
    {
        $pekerja = $this->karyawan('pekerja1');
        $pengganti = $this->karyawan('pengganti1');

        $spk = $this->spk([
            'date' => '2026-10-05',
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-07',
            'duration_days' => 3,
            'spk_value' => 7500000,
        ]);

        $members = [
            ['employee_id' => $pekerja->employee->id, 'role' => 'worker', 'days_worked' => 1],
            ['employee_id' => $pengganti->employee->id, 'role' => 'replacement', 'replacement_for_id' => $pekerja->employee->id, 'days_worked' => 2],
        ];

        // Empat pekerja lain masuk penuh (3 hari) agar total slot menjadi 5 orang.
        foreach (range(1, 4) as $i) {
            $lain = $this->karyawan('lain'.$i);
            $members[] = ['employee_id' => $lain->employee->id, 'role' => 'worker', 'days_worked' => 3];
        }

        $this->addMembers($spk, $members);

        // 7.500.000 / (5 orang x 3 hari) = 500.000 per hari.
        $dataPekerja = $this->preview($pekerja->employee->id);
        $this->assertSame(1, $dataPekerja['spk_count']);
        $this->assertEqualsWithDelta(500000, (float) $dataPekerja['items'][0]['share_amount'], 0.01);
        $this->assertEqualsWithDelta(500000, (float) $dataPekerja['gross_share'], 0.01);

        // Pekerja: 500.000 - 4% = 480.000 - 22% = 374.400 - 100.000 uang makan.
        $this->assertEqualsWithDelta(20000, (float) $dataPekerja['ppn_amount'], 0.01);
        $this->assertEqualsWithDelta(105600, (float) $dataPekerja['commission_amount'], 0.01);
        $this->assertEqualsWithDelta(100000, (float) $dataPekerja['meal_deduction'], 0.01);
        $this->assertEqualsWithDelta(274400, (float) $dataPekerja['net_salary'], 0.01);

        // Pengganti masuk 2 hari -> 1.000.000.
        $dataPengganti = $this->preview($pengganti->employee->id);
        $this->assertEqualsWithDelta(1000000, (float) $dataPengganti['gross_share'], 0.01);
        $this->assertEqualsWithDelta(648800, (float) $dataPengganti['net_salary'], 0.01);
    }

    /**
     * Contoh dari pemilik bengkel: dua SPK masing-masing 2,5 juta,
     * SPK A 4 orang, SPK B 3 orang, satu orang ikut keduanya.
     */
    public function test_dua_spk_dijumlahkan_lalu_dipotong_ppn_komisi_dan_uang_makan(): void
    {
        $budi = $this->karyawan('budi');

        $spkA = $this->spk(['date' => '2026-10-05', 'spk_value' => 2500000]);
        $membersA = [['employee_id' => $budi->employee->id]];
        foreach (range(1, 3) as $i) {
            $membersA[] = ['employee_id' => $this->karyawan('a'.$i)->employee->id];
        }
        $this->addMembers($spkA, $membersA);

        $spkB = $this->spk(['date' => '2026-10-12', 'spk_value' => 2500000]);
        $membersB = [['employee_id' => $budi->employee->id]];
        foreach (range(1, 2) as $i) {
            $membersB[] = ['employee_id' => $this->karyawan('b'.$i)->employee->id];
        }
        $this->addMembers($spkB, $membersB);

        $data = $this->preview($budi->employee->id);

        // 2.500.000/4 = 625.000 dan 2.500.000/3 = 833.333,33
        $this->assertSame(2, $data['spk_count']);
        $this->assertEqualsWithDelta(625000, (float) $data['items'][0]['share_amount'], 0.01);
        $this->assertEqualsWithDelta(833333.33, (float) $data['items'][1]['share_amount'], 0.01);
        $this->assertEqualsWithDelta(1458333.33, (float) $data['gross_share'], 0.01);

        $this->assertEqualsWithDelta(58333.33, (float) $data['ppn_amount'], 0.01);
        $this->assertEqualsWithDelta(308000, (float) $data['commission_amount'], 0.01);

        // Uang makan dipotong per SPK yang diikuti: 2 x 100.000.
        $this->assertSame(2, $data['meal_count']);
        $this->assertEqualsWithDelta(200000, (float) $data['meal_deduction'], 0.01);

        // 1.458.333,33 -> 1.400.000 -> 1.092.000 -> 892.000
        $this->assertEqualsWithDelta(892000, (float) $data['net_salary'], 0.01);
        $this->assertFalse($data['is_negative']);
    }

    public function test_pengganti_tidak_menambah_jumlah_pembagi(): void
    {
        $pekerja = $this->karyawan('pekerja1');
        $pengganti = $this->karyawan('pengganti1');

        $spk = $this->spk(['date' => '2026-10-05', 'spk_value' => 7500000, 'duration_days' => 3]);

        $members = [
            ['employee_id' => $pekerja->employee->id, 'days_worked' => 1],
            ['employee_id' => $pengganti->employee->id, 'role' => 'replacement', 'replacement_for_id' => $pekerja->employee->id, 'days_worked' => 2],
        ];
        foreach (range(1, 4) as $i) {
            $members[] = ['employee_id' => $this->karyawan('lain'.$i)->employee->id, 'days_worked' => 3];
        }
        $this->addMembers($spk, $members);

        $detail = $this->auth($this->adminToken())
            ->getJson('/api/spks/'.$spk->id)
            ->assertOk()
            ->json('data');

        // 5 pekerja (pengganti tidak dihitung), 6 baris anggota.
        $this->assertSame(5, $detail['slot_count']);
        $this->assertCount(6, $detail['members']);
        $this->assertEqualsWithDelta(500000, (float) $detail['daily_rate'], 0.01);

        $penggantiRow = collect($detail['members'])->firstWhere('role', 'replacement');
        $this->assertSame('Pekerja1', $penggantiRow['replacement_for_name']);
        $this->assertSame(2, $penggantiRow['days_worked']);
    }

    public function test_hasil_minus_tetap_ditampilkan_minus(): void
    {
        $budi = $this->karyawan('budi');

        // SPK kecil: 500.000 dibagi 5 orang = 100.000 per orang.
        $spk = $this->spk(['date' => '2026-10-05', 'spk_value' => 500000]);
        $members = [['employee_id' => $budi->employee->id]];
        foreach (range(1, 4) as $i) {
            $members[] = ['employee_id' => $this->karyawan('k'.$i)->employee->id];
        }
        $this->addMembers($spk, $members);

        $data = $this->preview($budi->employee->id);

        // 100.000 - 4.000 = 96.000 - 21.120 = 74.880 - 100.000 uang makan = -25.120
        $this->assertEqualsWithDelta(-25120, (float) $data['net_salary'], 0.01);
        $this->assertTrue($data['is_negative']);
    }

    public function test_slip_menyimpan_rincian_setiap_spk(): void
    {
        $budi = $this->karyawan('budi');

        $spkA = $this->spk(['date' => '2026-10-05', 'spk_value' => 2500000]);
        $this->addMembers($spkA, [
            ['employee_id' => $budi->employee->id],
            ['employee_id' => $this->karyawan('x1')->employee->id],
            ['employee_id' => $this->karyawan('x2')->employee->id],
            ['employee_id' => $this->karyawan('x3')->employee->id],
        ]);

        $spkB = $this->spk(['date' => '2026-10-12', 'spk_value' => 2500000]);
        $this->addMembers($spkB, [
            ['employee_id' => $budi->employee->id],
            ['employee_id' => $this->karyawan('y1')->employee->id],
            ['employee_id' => $this->karyawan('y2')->employee->id],
        ]);

        $slip = $this->auth($this->adminToken())
            ->postJson('/api/payrolls', ['employee_id' => $budi->employee->id, 'period' => '2026-10'])
            ->assertStatus(201)
            ->json('data');

        $this->assertStringStartsWith('SLIP-', $slip['slip_number']);
        $this->assertCount(2, $slip['items']);
        $this->assertEqualsWithDelta(892000, (float) $slip['net_salary'], 0.01);

        $detail = $this->auth($this->adminToken())
            ->getJson('/api/payrolls/'.$slip['id'])
            ->assertOk()
            ->json('data');

        $this->assertCount(2, $detail['items']);
        $this->assertEqualsWithDelta(625000, (float) $detail['items'][0]['share_amount'], 0.01);
        $this->assertEqualsWithDelta(833333.33, (float) $detail['items'][1]['share_amount'], 0.01);
    }

    public function test_satu_karyawan_hanya_punya_satu_slip_per_periode(): void
    {
        $budi = $this->karyawan('budi');
        $spk = $this->spk(['date' => '2026-10-05']);
        $this->addMembers($spk, [['employee_id' => $budi->employee->id]]);

        $this->auth($this->adminToken())
            ->postJson('/api/payrolls', ['employee_id' => $budi->employee->id, 'period' => '2026-10'])
            ->assertStatus(201);

        $this->auth($this->adminToken())
            ->postJson('/api/payrolls', ['employee_id' => $budi->employee->id, 'period' => '2026-10'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Karyawan ini sudah memiliki slip gaji untuk periode 2026-10.');
    }

    public function test_slip_bisa_dibuat_sekaligus_untuk_semua_karyawan(): void
    {
        $budi = $this->karyawan('budi');
        $sari = $this->karyawan('sari');
        $lain = $this->karyawan('lain');

        $spk = $this->spk(['date' => '2026-10-05', 'spk_value' => 3000000]);
        $this->addMembers($spk, [
            ['employee_id' => $budi->employee->id],
            ['employee_id' => $sari->employee->id],
            ['employee_id' => $lain->employee->id],
        ]);

        $hasil = $this->auth($this->adminToken())
            ->postJson('/api/payrolls/generate', ['period' => '2026-10'])
            ->assertStatus(201)
            ->json('data');

        $this->assertSame(3, $hasil['created_count']);
        $this->assertSame(0, $hasil['skipped_count']);

        // Dijalankan ulang: semua dilewati karena slip sudah ada.
        $ulang = $this->auth($this->adminToken())
            ->postJson('/api/payrolls/generate', ['period' => '2026-10'])
            ->assertStatus(201)
            ->json('data');

        $this->assertSame(0, $ulang['created_count']);
        $this->assertSame(3, $ulang['skipped_count']);
    }

    public function test_karyawan_tanpa_spk_pada_periode_tidak_bisa_dibuatkan_slip(): void
    {
        $budi = $this->karyawan('budi');

        $this->auth($this->adminToken())
            ->postJson('/api/payrolls/preview', ['employee_id' => $budi->employee->id, 'period' => '2026-10'])
            ->assertOk()
            ->assertJsonPath('data.spk_count', 0);

        $this->auth($this->adminToken())
            ->postJson('/api/payrolls', ['employee_id' => $budi->employee->id, 'period' => '2026-10'])
            ->assertStatus(422);
    }

    public function test_potongan_kasbon_tidak_boleh_melebihi_sisa_utang(): void
    {
        $budi = $this->karyawan('budi');
        $this->cashAdvance($budi->employee, 50000);

        $spk = $this->spk(['date' => '2026-10-05', 'spk_value' => 1000000]);
        $this->addMembers($spk, [['employee_id' => $budi->employee->id]]);

        $this->auth($this->adminToken())
            ->postJson('/api/payrolls/preview', [
                'employee_id' => $budi->employee->id,
                'period' => '2026-10',
                'cash_advance_deduction' => 999999,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        // Tanpa diisi, kasbon dipotong otomatis sebesar sisa utang.
        $data = $this->preview($budi->employee->id);
        $this->assertEqualsWithDelta(50000, (float) $data['cash_advance_deduction'], 0.01);
    }

    public function test_periode_harus_format_tahun_bulan(): void
    {
        $budi = $this->karyawan('budi');

        foreach (['Oktober', '2026-13'] as $periode) {
            $this->auth($this->adminToken())
                ->postJson('/api/payrolls', ['employee_id' => $budi->employee->id, 'period' => $periode])
                ->assertStatus(422);
        }
    }

    public function test_karyawan_hanya_bisa_melihat_slip_miliknya(): void
    {
        $budi = $this->karyawan('budi');
        $sari = $this->karyawan('sari');

        $spk = $this->spk(['date' => '2026-10-05', 'spk_value' => 2000000]);
        $this->addMembers($spk, [
            ['employee_id' => $budi->employee->id],
            ['employee_id' => $sari->employee->id],
        ]);

        $slipBudi = $this->auth($this->adminToken())
            ->postJson('/api/payrolls', ['employee_id' => $budi->employee->id, 'period' => '2026-10'])
            ->assertStatus(201)
            ->json('data');

        $slipSari = $this->auth($this->adminToken())
            ->postJson('/api/payrolls', ['employee_id' => $sari->employee->id, 'period' => '2026-10'])
            ->assertStatus(201)
            ->json('data');

        $this->asToken($this->tokenFor($budi))
            ->getJson('/api/karyawan/payrolls/'.$slipBudi['id'])
            ->assertOk();

        $this->asToken($this->tokenFor($budi))
            ->getJson('/api/karyawan/payrolls/'.$slipSari['id'])
            ->assertStatus(403);
    }

    /**
     * Aplikasi versi lama masih mengirim spk_id saja; harus tetap jalan.
     */
    public function test_klien_lama_masih_bisa_membuat_slip_dengan_spk_id(): void
    {
        $budi = $this->karyawan('budi');

        $spk = $this->auth($this->adminToken())
            ->postJson('/api/spks', [
                'date' => '2026-10-05',
                'shop_id' => $this->shop('SHOP-LAMA')->id,
                'job_name' => 'Neon Box',
                'spk_value' => 1000000,
                'employee_id' => $budi->employee->id,
            ])
            ->assertStatus(201)
            ->json('data');

        // Tanpa daftar anggota, karyawan pada SPK otomatis jadi pekerja tunggal.
        $this->assertSame(1, $spk['slot_count']);

        $slip = $this->auth($this->adminToken())
            ->postJson('/api/payrolls', ['spk_id' => $spk['id'], 'period' => '2026-10'])
            ->assertStatus(201)
            ->json('data');

        $this->assertCount(1, $slip['items']);
        $this->assertEqualsWithDelta(648800, (float) $slip['net_salary'], 0.01);
    }
}
