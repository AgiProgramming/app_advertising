<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesDomainData;
use Tests\TestCase;

/**
 * Pengujian logika perhitungan gaji dan siklus hidup slip gaji.
 *
 * Rumus yang diuji (sesuai PayrollService):
 *   PPN          = 3% x nilai SPK
 *   Nilai bersih = nilai SPK - PPN
 *   Gaji kotor   = persentase komisi (default 22%) x nilai bersih
 *   Gaji bersih  = gaji kotor - tunjangan makan - potongan kasbon
 */
class PayrollCalculationTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDomainData;

    private function adminToken(): string
    {
        return $this->tokenFor($this->admin());
    }

    private function auth(string $token): static
    {
        return $this->withHeader('Authorization', 'Bearer ' . $token);
    }

    public function test_perhitungan_default_ppn_3_persen_dan_komisi_22_persen(): void
    {
        $karyawan = $this->karyawan('budi', ['nik' => 'EMP-BUDI']);
        $spk = $this->spk(['employee_id' => $karyawan->employee->id, 'spk_value' => 1000000]);

        $data = $this->auth($this->adminToken())
            ->postJson('/api/payrolls/preview', ['spk_id' => $spk->id])
            ->assertOk()
            ->json('data');

        $this->assertEqualsWithDelta(30000, (float) $data['ppn_amount'], 0.01);
        $this->assertEqualsWithDelta(970000, (float) $data['net_spk_value'], 0.01);
        $this->assertEqualsWithDelta(22, (float) $data['salary_percentage'], 0.01);
        $this->assertEqualsWithDelta(213400, (float) $data['gross_salary'], 0.01);
        $this->assertEqualsWithDelta(0, (float) $data['cash_advance_deduction'], 0.01);
        $this->assertEqualsWithDelta(213400, (float) $data['net_salary'], 0.01);
    }

    public function test_persentase_komisi_dapat_diubah(): void
    {
        $karyawan = $this->karyawan('budi', ['nik' => 'EMP-BUDI']);
        $spk = $this->spk(['employee_id' => $karyawan->employee->id, 'spk_value' => 1000000]);

        $data = $this->auth($this->adminToken())
            ->postJson('/api/payrolls/preview', ['spk_id' => $spk->id, 'salary_percentage' => 50])
            ->assertOk()
            ->json('data');

        $this->assertEqualsWithDelta(485000, (float) $data['gross_salary'], 0.01);
        $this->assertEqualsWithDelta(485000, (float) $data['net_salary'], 0.01);
    }

    public function test_tunjangan_makan_dikurangkan_dari_gaji_kotor(): void
    {
        $karyawan = $this->karyawan('budi', ['nik' => 'EMP-BUDI']);
        $spk = $this->spk(['employee_id' => $karyawan->employee->id, 'spk_value' => 1000000]);

        $data = $this->auth($this->adminToken())
            ->postJson('/api/payrolls/preview', ['spk_id' => $spk->id, 'meal_allowance' => 50000])
            ->assertOk()
            ->json('data');

        $this->assertEqualsWithDelta(50000, (float) $data['meal_allowance'], 0.01);
        $this->assertEqualsWithDelta(163400, (float) $data['net_salary'], 0.01);
    }

    public function test_potongan_kasbon_otomatis_sebesar_sisa_kasbon(): void
    {
        $karyawan = $this->karyawan('budi', ['nik' => 'EMP-BUDI']);
        $this->cashAdvance($karyawan->employee, 100000);
        $spk = $this->spk(['employee_id' => $karyawan->employee->id, 'spk_value' => 1000000]);

        $data = $this->auth($this->adminToken())
            ->postJson('/api/payrolls/preview', ['spk_id' => $spk->id])
            ->assertOk()
            ->json('data');

        $this->assertEqualsWithDelta(100000, (float) $data['remaining_cash_advance'], 0.01);
        $this->assertEqualsWithDelta(100000, (float) $data['cash_advance_deduction'], 0.01);
        $this->assertEqualsWithDelta(113400, (float) $data['net_salary'], 0.01);
    }

    public function test_potongan_kasbon_otomatis_tidak_melebihi_gaji_kotor(): void
    {
        $karyawan = $this->karyawan('budi', ['nik' => 'EMP-BUDI']);
        $this->cashAdvance($karyawan->employee, 500000);
        $spk = $this->spk(['employee_id' => $karyawan->employee->id, 'spk_value' => 1000000]);

        $data = $this->auth($this->adminToken())
            ->postJson('/api/payrolls/preview', ['spk_id' => $spk->id])
            ->assertOk()
            ->json('data');

        // Gaji kotor 213.400 < kasbon 500.000 -> potongan dibatasi gaji kotor, sisa 0.
        $this->assertEqualsWithDelta(213400, (float) $data['cash_advance_deduction'], 0.01);
        $this->assertEqualsWithDelta(0, (float) $data['net_salary'], 0.01);
    }

    public function test_potongan_kasbon_manual_melebihi_sisa_utang_ditolak(): void
    {
        $karyawan = $this->karyawan('budi', ['nik' => 'EMP-BUDI']);
        $this->cashAdvance($karyawan->employee, 100000);
        $spk = $this->spk(['employee_id' => $karyawan->employee->id, 'spk_value' => 1000000]);

        $this->auth($this->adminToken())
            ->postJson('/api/payrolls/preview', [
                'spk_id' => $spk->id,
                'cash_advance_deduction' => 999999999,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_tunjangan_makan_melebihi_gaji_kotor_ditolak(): void
    {
        $karyawan = $this->karyawan('budi', ['nik' => 'EMP-BUDI']);
        $spk = $this->spk(['employee_id' => $karyawan->employee->id, 'spk_value' => 1000000]);

        $this->auth($this->adminToken())
            ->postJson('/api/payrolls/preview', [
                'spk_id' => $spk->id,
                'meal_allowance' => 999999999,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_spk_tanpa_karyawan_tidak_bisa_dibuatkan_slip(): void
    {
        $spk = $this->spk(['employee_id' => null]);

        $this->auth($this->adminToken())
            ->postJson('/api/payrolls/preview', ['spk_id' => $spk->id])
            ->assertStatus(422);
    }

    public function test_slip_gaji_tersimpan_dengan_nilai_hasil_perhitungan(): void
    {
        $karyawan = $this->karyawan('budi', ['nik' => 'EMP-BUDI']);
        $spk = $this->spk(['employee_id' => $karyawan->employee->id, 'spk_value' => 1000000]);

        $data = $this->auth($this->adminToken())
            ->postJson('/api/payrolls', ['spk_id' => $spk->id, 'period' => '2026-10'])
            ->assertStatus(201)
            ->json('data');

        $this->assertStringStartsWith('SLIP-', $data['slip_number']);
        $this->assertEqualsWithDelta(213400, (float) $data['net_salary'], 0.01);
        $this->assertEqualsWithDelta(30000, (float) $data['original_spk_value'] - (float) $data['net_spk_value'], 0.01);
    }

    public function test_satu_spk_hanya_boleh_punya_satu_slip(): void
    {
        $karyawan = $this->karyawan('budi', ['nik' => 'EMP-BUDI']);
        $spk = $this->spk(['employee_id' => $karyawan->employee->id]);

        $this->auth($this->adminToken())
            ->postJson('/api/payrolls', ['spk_id' => $spk->id, 'period' => '2026-10'])
            ->assertStatus(201);

        $this->auth($this->adminToken())
            ->postJson('/api/payrolls', ['spk_id' => $spk->id, 'period' => '2026-10'])
            ->assertStatus(422);
    }

    public function test_periode_gaji_harus_format_tahun_bulan(): void
    {
        $karyawan = $this->karyawan('budi', ['nik' => 'EMP-BUDI']);
        $spk = $this->spk(['employee_id' => $karyawan->employee->id]);

        $this->auth($this->adminToken())
            ->postJson('/api/payrolls', ['spk_id' => $spk->id, 'period' => 'Oktober'])
            ->assertStatus(422);

        $this->auth($this->adminToken())
            ->postJson('/api/payrolls', ['spk_id' => $spk->id, 'period' => '2026-13'])
            ->assertStatus(422);
    }

    public function test_status_spk_menjadi_selesai_saat_slip_dibuat(): void
    {
        $karyawan = $this->karyawan('budi', ['nik' => 'EMP-BUDI']);
        $spk = $this->spk([
            'employee_id' => $karyawan->employee->id,
            'job_status' => 'Sedang Dikerjakan',
        ]);

        $this->auth($this->adminToken())
            ->postJson('/api/payrolls', ['spk_id' => $spk->id, 'period' => '2026-10'])
            ->assertStatus(201);

        $this->assertSame('Selesai', $spk->fresh()->job_status);
        $this->assertSame('Sedang Dikerjakan', $spk->fresh()->payroll->previous_job_status);
    }

    public function test_status_spk_kembali_saat_slip_dihapus(): void
    {
        $karyawan = $this->karyawan('budi', ['nik' => 'EMP-BUDI']);
        $spk = $this->spk([
            'employee_id' => $karyawan->employee->id,
            'job_status' => 'Sedang Dikerjakan',
        ]);

        $slip = $this->auth($this->adminToken())
            ->postJson('/api/payrolls', ['spk_id' => $spk->id, 'period' => '2026-10'])
            ->assertStatus(201)
            ->json('data');

        $this->assertSame('Selesai', $spk->fresh()->job_status);

        $this->auth($this->adminToken())
            ->deleteJson('/api/payrolls/' . $slip['id'])
            ->assertOk();

        $this->assertSame('Sedang Dikerjakan', $spk->fresh()->job_status);
        $this->assertSame('Belum Dibayar', $spk->fresh()->payment_status);
    }

    public function test_status_pembayaran_slip_menyesuaikan_spk(): void
    {
        $karyawan = $this->karyawan('budi', ['nik' => 'EMP-BUDI']);
        $spk = $this->spk(['employee_id' => $karyawan->employee->id]);

        $slip = $this->auth($this->adminToken())
            ->postJson('/api/payrolls', ['spk_id' => $spk->id, 'period' => '2026-10'])
            ->assertStatus(201)
            ->json('data');

        $updated = $this->auth($this->adminToken())
            ->patchJson('/api/payrolls/' . $slip['id'] . '/payment', [
                'payment_status' => 'Sudah Dibayar',
                'payment_date' => '2026-10-17',
            ])
            ->assertOk()
            ->json('data');

        $this->assertSame('Sudah Dibayar', $updated['payment_status']);
        $this->assertSame('2026-10-17', $updated['payment_date']);
        $this->assertSame('Sudah Dibayar', $spk->fresh()->payment_status);
    }

    public function test_karyawan_bisa_melihat_slip_miliknya_sendiri(): void
    {
        $karyawan = $this->karyawan('budi', ['nik' => 'EMP-BUDI']);
        $spk = $this->spk(['employee_id' => $karyawan->employee->id]);
        $slip = $this->payroll($karyawan->employee, $spk);

        $this->auth($this->tokenFor($karyawan))
            ->getJson('/api/karyawan/payrolls/' . $slip->id)
            ->assertOk()
            ->assertJsonPath('data.id', $slip->id);
    }
}
