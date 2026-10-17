<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreatesDomainData;
use Tests\TestCase;

/**
 * Notifikasi slip gaji: feed in-app + push FCM (HTTP di-fake, tanpa jaringan).
 */
class NotificationTest extends TestCase
{
    use CreatesDomainData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->fakeFirebase();
    }

    /**
     * Service account palsu (kunci RSA khusus uji, ada di tests/fixtures) +
     * respons Google di-fake, sehingga tidak butuh kredensial asli maupun internet.
     */
    private function fakeFirebase(): void
    {
        config([
            'services.firebase.credentials' => base_path('tests/fixtures/fake-firebase-service-account.json'),
            'services.firebase.project_id' => 'mann-plus-test',
            'services.firebase.ca_bundle' => null,
        ]);

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'fake-access-token', 'expires_in' => 3600]),
            'fcm.googleapis.com/*' => Http::response(['name' => 'projects/mann-plus-test/messages/1']),
        ]);
    }

    public function test_device_token_dapat_didaftarkan_dan_dihapus(): void
    {
        $karyawan = $this->karyawan('budi');
        $token = $this->tokenFor($karyawan);

        $this->asToken($token)
            ->postJson('/api/device-token', ['token' => 'token-perangkat-uji', 'platform' => 'android'])
            ->assertStatus(201);

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $karyawan->id,
            'token' => 'token-perangkat-uji',
        ]);

        $this->asToken($token)
            ->deleteJson('/api/device-token', ['token' => 'token-perangkat-uji'])
            ->assertOk();

        $this->assertDatabaseMissing('device_tokens', ['token' => 'token-perangkat-uji']);
    }

    public function test_slip_terbit_membuat_notifikasi_dan_mengirim_push(): void
    {
        $karyawan = $this->karyawan('budi', ['nik' => 'EMP-BUDI']);
        $this->asToken($this->tokenFor($karyawan))
            ->postJson('/api/device-token', ['token' => 'token-fcm-budi', 'platform' => 'android'])
            ->assertStatus(201);

        $adminToken = $this->tokenFor($this->admin());
        $spk = $this->spk(['employee_id' => $karyawan->employee->id, 'spk_value' => 1000000]);

        $this->asToken($adminToken)
            ->postJson('/api/payrolls', ['spk_id' => $spk->id, 'period' => '2026-10'])
            ->assertStatus(201);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $karyawan->id,
            'type' => 'payroll_issued',
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'fcm.googleapis.com/v1/projects/mann-plus-test/messages:send')
                && $request['message']['token'] === 'token-fcm-budi'
                && str_contains($request['message']['notification']['title'], 'Slip gaji');
        });
    }

    public function test_slip_dibayar_membuat_notifikasi_pembayaran(): void
    {
        $karyawan = $this->karyawan('budi', ['nik' => 'EMP-BUDI']);
        $adminToken = $this->tokenFor($this->admin());
        $spk = $this->spk(['employee_id' => $karyawan->employee->id]);

        $slip = $this->asToken($adminToken)
            ->postJson('/api/payrolls', ['spk_id' => $spk->id, 'period' => '2026-10'])
            ->assertStatus(201)
            ->json('data');

        $this->asToken($adminToken)
            ->patchJson('/api/payrolls/'.$slip['id'].'/payment', [
                'payment_status' => 'Sudah Dibayar',
                'payment_date' => '2026-10-17',
            ])
            ->assertOk();

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $karyawan->id,
            'type' => 'payroll_paid',
        ]);
    }

    public function test_notifikasi_hanya_bisa_dibaca_pemiliknya(): void
    {
        $budi = $this->karyawan('budi', ['nik' => 'EMP-BUDI']);
        $siti = $this->karyawan('siti', ['nik' => 'EMP-SITI']);

        $notif = AppNotification::create([
            'user_id' => $budi->id,
            'title' => 'Slip gaji Oktober 2026 sudah terbit',
            'body' => 'Rp 212.367,02',
            'type' => 'payroll_issued',
        ]);

        $this->asToken($this->tokenFor($siti))
            ->patchJson('/api/notifications/'.$notif->id.'/read')
            ->assertStatus(404);

        $this->asToken($this->tokenFor($budi))
            ->patchJson('/api/notifications/'.$notif->id.'/read')
            ->assertOk()
            ->assertJsonPath('unread_count', 0);
    }

    public function test_daftar_notifikasi_menampilkan_jumlah_belum_dibaca(): void
    {
        $budi = $this->karyawan('budi');

        foreach (['Satu', 'Dua'] as $title) {
            AppNotification::create([
                'user_id' => $budi->id,
                'title' => $title,
                'body' => 'isi',
                'type' => 'info',
            ]);
        }

        $this->asToken($this->tokenFor($budi))
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 2)
            ->assertJsonCount(2, 'data');

        $this->asToken($this->tokenFor($budi))
            ->postJson('/api/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('unread_count', 0);
    }
}
