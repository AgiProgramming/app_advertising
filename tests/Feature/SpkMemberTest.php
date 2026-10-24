<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesDomainData;
use Tests\TestCase;

/**
 * Pengujian pengelolaan anggota SPK: banyak orang per SPK, peran pengganti,
 * dan durasi kerja yang menentukan nilai harian.
 */
class SpkMemberTest extends TestCase
{
    use CreatesDomainData;
    use RefreshDatabase;

    private function auth(string $token): static
    {
        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    public function test_spk_disimpan_dengan_beberapa_anggota_dan_pengganti(): void
    {
        $adminToken = $this->tokenFor($this->admin());

        $pekerja = $this->karyawan('pekerja1');
        $pengganti = $this->karyawan('pengganti1');
        $lain = $this->karyawan('lain1');

        $payload = [
            'date' => '2026-10-05',
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-07',
            'shop_id' => $this->shop('SHOP-SPK')->id,
            'job_name' => 'Neon Box Indomaret',
            'client_name' => 'PT Albany Corona Lestari',
            'location' => 'T4PO - Gebang Kulon',
            'spk_value' => 7500000,
            'members' => [
                ['employee_id' => $pekerja->employee->id, 'role' => 'worker', 'days_worked' => 1],
                ['employee_id' => $pengganti->employee->id, 'role' => 'replacement', 'replacement_for_id' => $pekerja->employee->id, 'days_worked' => 2],
                ['employee_id' => $lain->employee->id, 'role' => 'worker', 'days_worked' => 3],
            ],
        ];

        $data = $this->auth($adminToken)
            ->postJson('/api/spks', $payload)
            ->assertStatus(201)
            ->json('data');

        // Durasi dihitung dari tanggal mulai & selesai (5,6,7 = 3 hari).
        $this->assertSame(3, $data['duration_days']);
        $this->assertSame(2, $data['slot_count']);
        $this->assertCount(3, $data['members']);

        // 7.500.000 / (2 orang x 3 hari) = 1.250.000 per hari.
        $this->assertEqualsWithDelta(1250000, (float) $data['daily_rate'], 0.01);

        $this->assertDatabaseHas('spk_employees', [
            'spk_id' => $data['id'],
            'employee_id' => $pengganti->employee->id,
            'role' => 'replacement',
            'replacement_for_id' => $pekerja->employee->id,
            'days_worked' => 2,
        ]);
    }

    public function test_hari_kerja_tidak_boleh_melebihi_durasi_spk(): void
    {
        $adminToken = $this->tokenFor($this->admin());
        $pekerja = $this->karyawan('pekerja1');

        $data = $this->auth($adminToken)
            ->postJson('/api/spks', [
                'date' => '2026-10-05',
                'start_date' => '2026-10-05',
                'end_date' => '2026-10-06',
                'shop_id' => $this->shop('SHOP-SPK2')->id,
                'job_name' => 'Stiker Nama Toko',
                'spk_value' => 1000000,
                'members' => [
                    ['employee_id' => $pekerja->employee->id, 'days_worked' => 99],
                ],
            ])
            ->assertStatus(201)
            ->json('data');

        $this->assertSame(2, $data['duration_days']);
        $this->assertSame(2, $data['members'][0]['days_worked']);
    }

    public function test_tanggal_selesai_sebelum_mulai_ditolak(): void
    {
        $this->auth($this->tokenFor($this->admin()))
            ->postJson('/api/spks', [
                'date' => '2026-10-05',
                'start_date' => '2026-10-07',
                'end_date' => '2026-10-05',
                'shop_id' => $this->shop('SHOP-SPK3')->id,
                'job_name' => 'Neon Box',
                'spk_value' => 1000000,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('end_date');
    }

    public function test_daftar_anggota_bisa_diganti_saat_spk_diubah(): void
    {
        $adminToken = $this->tokenFor($this->admin());
        $budi = $this->karyawan('budi');
        $sari = $this->karyawan('sari');

        $spk = $this->spk(['date' => '2026-10-05', 'spk_value' => 3000000]);
        $this->addMembers($spk, [['employee_id' => $budi->employee->id]]);

        $data = $this->auth($adminToken)
            ->putJson('/api/spks/'.$spk->id, [
                'date' => '2026-10-05',
                'shop_id' => $spk->shop_id,
                'job_name' => 'Neon Box Revisi',
                'spk_value' => 3000000,
                'members' => [
                    ['employee_id' => $budi->employee->id],
                    ['employee_id' => $sari->employee->id],
                ],
            ])
            ->assertOk()
            ->json('data');

        $this->assertSame(2, $data['slot_count']);
        $this->assertDatabaseMissing('spk_employees', ['spk_id' => $spk->id, 'employee_id' => $sari->employee->id, 'role' => 'replacement']);
        $this->assertDatabaseHas('spk_employees', ['spk_id' => $spk->id, 'employee_id' => $sari->employee->id]);
    }

    public function test_karyawan_hanya_melihat_spk_tempat_dia_bertugas(): void
    {
        $budi = $this->karyawan('budi');
        $sari = $this->karyawan('sari');

        $spkBudi = $this->spk(['date' => '2026-10-05', 'job_name' => 'SPK Budi']);
        $this->addMembers($spkBudi, [['employee_id' => $budi->employee->id]]);

        $spkSari = $this->spk(['date' => '2026-10-06', 'job_name' => 'SPK Sari']);
        $this->addMembers($spkSari, [['employee_id' => $sari->employee->id]]);

        $daftar = $this->asToken($this->tokenFor($budi))
            ->getJson('/api/karyawan/spks')
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $daftar);
        $this->assertSame('SPK Budi', $daftar[0]['job_name']);
    }
}
