<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Menyiapkan database produksi setelah migrasi: peran dasar + satu akun admin.
 *
 * Aman dijalankan berulang kali (idempoten) — dipanggil otomatis oleh deploy.php
 * setiap kali deploy, tapi hanya membuat akun kalau belum ada.
 *
 * Password diambil dari variabel lingkungan INIT_ADMIN_PASSWORD di .env server.
 * Tidak pernah dicetak ke log supaya tidak bocor ke keluaran GitHub Actions.
 */
class InitAdmin extends Command
{
    protected $signature = 'app:init-admin {--username=admin_utama}';

    protected $description = 'Siapkan peran dasar dan akun admin pertama (aman dijalankan berulang)';

    public function handle(): int
    {
        foreach (['Admin', 'Owner', 'Karyawan'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        $username = trim((string) $this->option('username'));

        $sudahAda = User::whereRaw('LOWER(username) = ?', [Str::lower($username)])->exists();

        if ($sudahAda) {
            $this->info("Akun {$username} sudah ada — dilewati.");

            return self::SUCCESS;
        }

        $password = (string) env('INIT_ADMIN_PASSWORD', '');

        if (strlen($password) < 8) {
            $this->error('INIT_ADMIN_PASSWORD belum diisi di .env server (minimal 8 karakter).');
            $this->line('Akun admin BELUM dibuat. Isi variabel tersebut lalu jalankan deploy lagi.');

            return self::FAILURE;
        }

        User::create([
            'role_id' => Role::where('name', 'Admin')->value('id'),
            'username' => $username,
            'password' => Hash::make($password),
        ]);

        $this->info("Akun admin {$username} berhasil dibuat.");

        return self::SUCCESS;
    }
}
