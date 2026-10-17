<?php

namespace Tests\Support;

use App\Models\CashAdvance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\Shop;
use App\Models\Spk;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Pembuat data uji untuk fitur penggajian.
 * Dipakai bersama oleh seluruh feature test.
 */
trait CreatesDomainData
{
    protected function role(string $name): Role
    {
        return Role::firstOrCreate(['name' => $name]);
    }

    protected function user(string $username, string $roleName, array $employee = [], string $password = 'password123'): User
    {
        $user = User::where('username', $username)->first();

        if (! $user) {
            $user = User::create([
                'role_id' => $this->role($roleName)->id,
                'username' => $username,
                'password' => Hash::make($password),
            ]);
        }

        if ($employee !== [] && ! $user->employee) {
            Employee::create(array_merge([
                'user_id' => $user->id,
                'nik' => strtoupper($username).'-NIK',
                'name' => ucfirst($username),
                'position' => 'Staf',
                'is_active' => true,
            ], $employee));
        }

        return $user->fresh();
    }

    protected function karyawan(string $username, array $employee = []): User
    {
        return $this->user($username, 'Karyawan', array_merge(['nik' => strtoupper($username).'-NIK'], $employee));
    }

    protected function admin(): User
    {
        return $this->user('admin_utama', 'Admin', ['nik' => 'EMP-ADM']);
    }

    protected function owner(): User
    {
        return $this->user('owner_bengkel', 'Owner');
    }

    protected function shop(string $code = 'SHOP-1'): Shop
    {
        return Shop::create([
            'code' => $code,
            'name' => 'Toko Uji '.$code,
            'owner_name' => 'Pemilik',
            'status' => 'Aktif',
        ]);
    }

    protected function spk(array $attributes = []): Spk
    {
        return Spk::create(array_merge([
            'spk_number' => 'SPK-TEST-'.uniqid(),
            'date' => now()->toDateString(),
            'shop_id' => $this->shop('SHOP-'.uniqid())->id,
            'job_name' => 'Neon Box',
            'spk_value' => 1000000,
            'job_status' => 'Belum Dikerjakan',
            'payment_status' => 'Belum Dibayar',
        ], $attributes));
    }

    protected function cashAdvance(Employee $employee, float $amount): CashAdvance
    {
        return CashAdvance::create([
            'employee_id' => $employee->id,
            'amount' => $amount,
            'date' => now()->toDateString(),
            'description' => 'Kasbon uji',
        ]);
    }

    protected function payroll(Employee $employee, Spk $spk, array $attributes = []): Payroll
    {
        return Payroll::create(array_merge([
            'slip_number' => 'SLIP-TEST-'.uniqid(),
            'employee_id' => $employee->id,
            'spk_id' => $spk->id,
            'period' => '2026-10',
            'original_spk_value' => 1000000,
            'net_spk_value' => 970000,
            'salary_percentage' => 22,
            'meal_allowance' => 0,
            'cash_advance_deduction' => 0,
            'net_salary' => 213400,
            'payment_status' => 'Belum Dibayar',
        ], $attributes));
    }

    protected function tokenFor(User $user): string
    {
        return $user->createToken('auth_token')->plainTextToken;
    }

    /**
     * Kirim request sebagai pengguna tertentu.
     * Guard Sanctum menyimpan user per proses, jadi harus di-reset dulu
     * agar pergantian pengguna di dalam satu test benar-benar berlaku.
     */
    protected function asToken(string $token): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    /**
     * Kirim request tanpa autentikasi (token tidak dikirim sama sekali).
     */
    protected function asGuest(): static
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        return $this;
    }
}
