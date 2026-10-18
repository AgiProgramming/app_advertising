<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;

/**
 * setpass.php — menyelaraskan password admin dengan nilai INIT_ADMIN_PASSWORD
 * yang tertulis di berkas .env (bukan lewat env(), karena setelah config:cache
 * env() mengembalikan null). Password tidak pernah dicetak.
 *
 * Sekali pakai: berkas ini menghapus dirinya sendiri.
 */
set_time_limit(0);
ini_set('display_errors', '0');
header('Content-Type: text/plain; charset=utf-8');

const APP = '/home/gonn2842/app';

// 1. Baca .env sebagai berkas biasa
$envFile = APP.'/.env';
$password = null;

foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), 'INIT_ADMIN_PASSWORD=')) {
        $password = trim(substr(trim($line), strlen('INIT_ADMIN_PASSWORD=')));
        $password = trim($password, "\"'");
    }
}

echo 'INIT_ADMIN_PASSWORD di .env: '.(($password !== null && strlen($password) >= 8) ? 'ada ('.strlen($password).' karakter)' : 'TIDAK ADA').PHP_EOL;

if ($password === null || strlen($password) < 8) {
    echo 'tidak bisa melanjutkan.'.PHP_EOL;
    @unlink(__FILE__);
    exit;
}

// 2. Setel ulang hash agar pasti cocok
require APP.'/vendor/autoload.php';
$laravel = require APP.'/bootstrap/app.php';
$laravel->make(Kernel::class)->bootstrap();

$user = User::where('username', 'admin_utama')->first();

if (! $user) {
    echo 'akun admin_utama tidak ada.'.PHP_EOL;
    @unlink(__FILE__);
    exit;
}

$user->password = Hash::make($password);
$user->save();

// 3. Verifikasi
$fresh = User::where('username', 'admin_utama')->first();
echo 'hash cocok setelah disetel: '.(password_verify($password, $fresh->password) ? 'COCOK' : 'TIDAK COCOK').PHP_EOL;

// 4. Cek login lewat jalur yang sama dengan aplikasi
$check = Hash::check($password, $fresh->password);
echo 'Hash::check: '.($check ? 'lolos' : 'gagal').PHP_EOL;

echo 'SELESAI'.PHP_EOL;
@unlink(__FILE__);
