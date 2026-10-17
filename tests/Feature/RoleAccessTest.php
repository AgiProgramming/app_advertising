<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesDomainData;
use Tests\TestCase;

/**
 * Pengujian otorisasi berbasis role dan proteksi objek (IDOR).
 */
class RoleAccessTest extends TestCase
{
    use CreatesDomainData;
    use RefreshDatabase;

    private function auth(string $token): static
    {
        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    public function test_karyawan_tidak_boleh_mengakses_master_data_admin(): void
    {
        $token = $this->tokenFor($this->karyawan('budi'));

        foreach ([
            '/api/employees',
            '/api/shops',
            '/api/payrolls',
            '/api/cash-advances',
            '/api/admin/dashboard',
        ] as $url) {
            $this->auth($token)->getJson($url)->assertStatus(403);
        }
    }

    public function test_karyawan_tidak_boleh_membuat_data_master(): void
    {
        $token = $this->tokenFor($this->karyawan('budi'));

        $this->auth($token)->postJson('/api/shops', [
            'code' => 'X-1',
            'name' => 'Toko Palsu',
        ])->assertStatus(403);
    }

    public function test_owner_tidak_boleh_mengakses_menu_admin(): void
    {
        $token = $this->tokenFor($this->owner());

        $this->auth($token)->getJson('/api/employees')->assertStatus(403);
        $this->auth($token)->getJson('/api/admin/dashboard')->assertStatus(403);
    }

    public function test_admin_tidak_boleh_mengakses_menu_owner(): void
    {
        $token = $this->tokenFor($this->admin());

        $this->auth($token)->getJson('/api/owner/dashboard')->assertStatus(403);
    }

    public function test_admin_bisa_mengakses_master_karyawan(): void
    {
        $token = $this->tokenFor($this->admin());

        $this->auth($token)->getJson('/api/employees')->assertOk();
    }

    public function test_owner_bisa_melihat_laporan_payroll(): void
    {
        $token = $this->tokenFor($this->owner());

        $this->auth($token)->getJson('/api/reports/payroll')
            ->assertOk()
            ->assertJsonPath('status', 'success');
    }

    public function test_karyawan_tidak_bisa_membuka_slip_karyawan_lain(): void
    {
        $budi = $this->karyawan('budi', ['nik' => 'EMP-BUDI']);
        $siti = $this->karyawan('siti', ['nik' => 'EMP-SITI']);

        $payrollSiti = $this->payroll($siti->employee, $this->spk(['employee_id' => $siti->employee->id]));

        $this->auth($this->tokenFor($budi))
            ->getJson('/api/karyawan/payrolls/'.$payrollSiti->id)
            ->assertStatus(403);
    }

    public function test_filter_employee_id_diabaikan_untuk_karyawan(): void
    {
        $budi = $this->karyawan('budi', ['nik' => 'EMP-BUDI']);
        $siti = $this->karyawan('siti', ['nik' => 'EMP-SITI']);

        $payrollBudi = $this->payroll($budi->employee, $this->spk(['employee_id' => $budi->employee->id]));
        $this->payroll($siti->employee, $this->spk(['employee_id' => $siti->employee->id]));

        $response = $this->auth($this->tokenFor($budi))
            ->getJson('/api/karyawan/payrolls?employee_id='.$siti->employee->id)
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertSame([$payrollBudi->id], $ids);
    }

    public function test_karyawan_hanya_melihat_spk_miliknya(): void
    {
        $budi = $this->karyawan('budi', ['nik' => 'EMP-BUDI']);
        $siti = $this->karyawan('siti', ['nik' => 'EMP-SITI']);

        $spkBudi = $this->spk(['employee_id' => $budi->employee->id]);
        $this->spk(['employee_id' => $siti->employee->id]);

        $response = $this->auth($this->tokenFor($budi))->getJson('/api/karyawan/spks')->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertSame([$spkBudi->id], $ids);
    }
}
