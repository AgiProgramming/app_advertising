<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Perintah ini dijalankan otomatis oleh deploy.php di hosting (tanpa SSH).
 * Yang penting dibuktikan: aman dijalankan berulang, dan tidak membuat akun
 * kalau password awalnya belum diatur.
 */
class InitAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        putenv('INIT_ADMIN_PASSWORD');

        parent::tearDown();
    }

    public function test_membuat_peran_dan_akun_admin_pertama(): void
    {
        putenv('INIT_ADMIN_PASSWORD=rahasia123');

        $this->artisan('app:init-admin', ['--username' => 'admin_uji'])
            ->assertSuccessful();

        $this->assertDatabaseHas('roles', ['name' => 'Admin']);
        $this->assertDatabaseHas('roles', ['name' => 'Owner']);
        $this->assertDatabaseHas('roles', ['name' => 'Karyawan']);
        $this->assertDatabaseHas('users', ['username' => 'admin_uji']);

        $user = User::where('username', 'admin_uji')->first();
        $this->assertSame('Admin', $user->role->name);
        $this->assertTrue(password_verify('rahasia123', $user->password));
    }

    public function test_dijalankan_dua_kali_tidak_menggandakan_akun(): void
    {
        putenv('INIT_ADMIN_PASSWORD=rahasia123');

        $this->artisan('app:init-admin', ['--username' => 'admin_uji'])->assertSuccessful();
        $this->artisan('app:init-admin', ['--username' => 'admin_uji'])
            ->expectsOutputToContain('sudah ada')
            ->assertSuccessful();

        $this->assertSame(1, User::where('username', 'admin_uji')->count());
    }

    public function test_tidak_membuat_akun_bila_password_awal_belum_diatur(): void
    {
        putenv('INIT_ADMIN_PASSWORD');

        $this->artisan('app:init-admin', ['--username' => 'admin_uji'])
            ->expectsOutputToContain('INIT_ADMIN_PASSWORD')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['username' => 'admin_uji']);
    }
}
