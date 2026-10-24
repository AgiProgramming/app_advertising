<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesDomainData;
use Tests\TestCase;

/**
 * Slip gaji digital: verifikasi QR publik dan unduhan PDF dengan kontrol akses.
 */
class SlipDocumentTest extends TestCase
{
    use CreatesDomainData;
    use RefreshDatabase;

    private function buatSlip(string $adminToken, array $spkAttributes = []): array
    {
        // Slip butuh SPK yang sudah punya karyawan.
        if (! isset($spkAttributes['employee_id'])) {
            $karyawan = $this->karyawan('pekerja-'.uniqid(), ['nik' => 'EMP-'.strtoupper(substr(uniqid(), -6))]);
            $spkAttributes['employee_id'] = $karyawan->employee->id;
        }

        $employeeId = $spkAttributes['employee_id'];

        $spk = $this->spk(array_merge(['date' => '2026-10-05'], $spkAttributes));
        $this->addMembers($spk, [['employee_id' => $employeeId]]);

        return $this->asToken($adminToken)
            ->postJson('/api/payrolls', ['employee_id' => $employeeId, 'period' => '2026-10'])
            ->assertStatus(201)
            ->json('data');
    }

    public function test_slip_baru_punya_token_verifikasi_dan_url(): void
    {
        $slip = $this->buatSlip($this->tokenFor($this->admin()));

        $this->assertNotEmpty($slip['verification_token']);
        $this->assertStringContainsString('/api/verify/'.$slip['verification_token'], $slip['verify_url']);
    }

    public function test_verifikasi_qr_publik_mengembalikan_ringkasan_slip(): void
    {
        $karyawan = $this->karyawan('budi', ['nik' => 'EMP-BUDI']);
        $slip = $this->buatSlip($this->tokenFor($this->admin()), ['employee_id' => $karyawan->employee->id]);

        $this->getJson('/api/verify/'.$slip['verification_token'])
            ->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('data.slip_number', $slip['slip_number'])
            ->assertJsonPath('data.employee_name', 'Budi');
    }

    public function test_nik_pada_verifikasi_disamarkan(): void
    {
        $karyawan = $this->karyawan('budi', ['nik' => 'EMP-123456']);
        $slip = $this->buatSlip($this->tokenFor($this->admin()), ['employee_id' => $karyawan->employee->id]);

        $nik = $this->getJson('/api/verify/'.$slip['verification_token'])->json('data.employee_nik');

        $this->assertStringStartsWith('EMP-', $nik);
        $this->assertStringContainsString('*', $nik);
    }

    public function test_token_verifikasi_palsu_ditolak(): void
    {
        $this->getJson('/api/verify/token-palsu-123')
            ->assertStatus(404)
            ->assertJsonPath('valid', false);
    }

    public function test_admin_bisa_mengunduh_pdf_slip(): void
    {
        $slip = $this->buatSlip($this->tokenFor($this->admin()));

        $response = $this->asToken($this->tokenFor($this->admin()))
            ->get('/api/payrolls/'.$slip['id'].'/pdf');

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));

        // dompdf mengembalikan Response biasa yang isinya byte PDF.
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_karyawan_bisa_mengunduh_slip_miliknya(): void
    {
        $karyawan = $this->karyawan('budi', ['nik' => 'EMP-BUDI']);
        $slip = $this->buatSlip($this->tokenFor($this->admin()), ['employee_id' => $karyawan->employee->id]);

        $this->asToken($this->tokenFor($karyawan))
            ->get('/api/payrolls/'.$slip['id'].'/pdf')
            ->assertOk();
    }

    public function test_karyawan_tidak_bisa_mengunduh_slip_karyawan_lain(): void
    {
        $budi = $this->karyawan('budi', ['nik' => 'EMP-BUDI']);
        $siti = $this->karyawan('siti', ['nik' => 'EMP-SITI']);

        $slipSiti = $this->buatSlip($this->tokenFor($this->admin()), ['employee_id' => $siti->employee->id]);

        $this->asToken($this->tokenFor($budi))
            ->getJson('/api/payrolls/'.$slipSiti['id'].'/pdf')
            ->assertStatus(403);
    }

    public function test_pdf_tanpa_login_ditolak(): void
    {
        $slip = $this->buatSlip($this->tokenFor($this->admin()));

        $this->asGuest()->getJson('/api/payrolls/'.$slip['id'].'/pdf')->assertStatus(401);
    }
}
