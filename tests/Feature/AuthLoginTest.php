<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesDomainData;
use Tests\TestCase;

/**
 * Pengujian autentikasi: login, backdoor, rate limit, akun nonaktif, logout.
 */
class AuthLoginTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDomainData;

    public function test_login_berhasil_dengan_kredensial_benar(): void
    {
        $this->admin();

        $response = $this->postJson('/api/login', [
            'username' => 'admin_utama',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'role', 'user'])
            ->assertJsonPath('role', 'Admin');
    }

    public function test_login_menerima_username_tanpa_membedakan_huruf_besar_kecil(): void
    {
        $this->user('PEDRO', 'Karyawan', ['nik' => 'EMP-PEDRO']);

        $this->postJson('/api/login', ['username' => 'pedro', 'password' => 'password123'])
            ->assertOk();
    }

    public function test_password_salah_ditolak(): void
    {
        $this->admin();

        $this->postJson('/api/login', ['username' => 'admin_utama', 'password' => 'salah'])
            ->assertStatus(401)
            ->assertJson(['message' => 'Username atau password salah.']);
    }

    public function test_username_tidak_dikenal_ditolak_dengan_pesan_sama(): void
    {
        $this->postJson('/api/login', ['username' => 'tidak_ada', 'password' => 'apa_saja'])
            ->assertStatus(401)
            ->assertJson(['message' => 'Username atau password salah.']);
    }

    /**
     * Regresi keamanan: password hardcoded milik role Owner harus benar-benar mati.
     */
    public function test_password_hardcoded_owner_tidak_berlaku_lagi(): void
    {
        $this->user('bapak_dollop', 'Owner', [], 'rahasia123');

        foreach (['dollop123', 'dollop', 'password123', 'dollop12345'] as $password) {
            $this->postJson('/api/login', [
                'username' => 'bapak_dollop',
                'password' => $password,
            ])->assertStatus(401);
        }

        $this->postJson('/api/login', [
            'username' => 'bapak_dollop',
            'password' => 'rahasia123',
        ])->assertOk();
    }

    /**
     * Regresi keamanan: username "ajaib" tidak boleh memetakan ke akun Owner.
     */
    public function test_username_ajaib_tidak_memetakan_ke_akun_owner(): void
    {
        $this->user('bapak_dollop', 'Owner', [], 'rahasia123');

        foreach (['dollop', 'owner', 'bapak dollop', 'owner_bengkel'] as $username) {
            $this->postJson('/api/login', [
                'username' => $username,
                'password' => 'rahasia123',
            ])->assertStatus(401);
        }
    }

    public function test_login_dibatasi_setelah_lima_percobaan(): void
    {
        $this->admin();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['username' => 'admin_utama', 'password' => 'salah'])
                ->assertStatus(401);
        }

        $this->postJson('/api/login', ['username' => 'admin_utama', 'password' => 'salah'])
            ->assertStatus(429)
            ->assertJsonStructure(['message', 'retry_after']);
    }

    public function test_karyawan_nonaktif_tidak_bisa_login(): void
    {
        $this->karyawan('budi', ['nik' => 'EMP-BUDI', 'is_active' => false]);

        $this->postJson('/api/login', ['username' => 'budi', 'password' => 'password123'])
            ->assertStatus(403);
    }

    public function test_token_tidak_berlaku_setelah_logout(): void
    {
        $user = $this->admin();
        $token = $this->tokenFor($user);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout')
            ->assertOk();

        // Guard di-cache per proses test; reset agar request berikutnya benar-benar memeriksa token.
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/profile')
            ->assertStatus(401);
    }

    public function test_tanpa_header_accept_tetap_menerima_json_401(): void
    {
        $response = $this->get('/api/profile');

        $response->assertStatus(401)->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_token_palsu_ditolak(): void
    {
        $this->withHeader('Authorization', 'Bearer 1|token-palsu')
            ->getJson('/api/profile')
            ->assertStatus(401);
    }
}
