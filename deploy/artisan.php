<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

/**
 * artisan.php — menjalankan perintah artisan di hosting tanpa SSH.
 *
 * Dipakai sekali saat pemasangan awal (setelah `bootstrap.php` memasang vendor).
 * Hanya menerima perintah dari daftar putih di bawah, dan menghapus dirinya
 * sendiri setelah semua perintah dijalankan.
 *
 * Contoh: https://domain/artisan.php?cmd=migrate
 */
set_time_limit(0);
ini_set('memory_limit', '512M');
ini_set('display_errors', '0');
header('Content-Type: text/plain; charset=utf-8');

const APP = '/home/gonn2842/app';

$whitelist = [
    'discover' => ['package:discover', []],
    'migrate' => ['migrate', ['--force' => true]],
    'init' => ['app:init-admin', []],
    'optimize' => ['optimize', []],
    'clear' => ['optimize:clear', []],
    'status' => ['migrate:status', []],
];

$key = $_GET['cmd'] ?? 'status';

// Cek apakah password di .env cocok dengan hash akun admin — tanpa mencetak password.
if ($key === 'cekpass') {
    require APP.'/vendor/autoload.php';
    $laravel = require APP.'/bootstrap/app.php';
    $laravel->make(Kernel::class)->bootstrap();

    $user = User::where('username', 'admin_utama')->first();
    $plain = (string) env('INIT_ADMIN_PASSWORD');

    echo 'akun ada: '.($user ? 'ya' : 'tidak').PHP_EOL;
    echo 'password di .env terisi: '.(strlen($plain) >= 8 ? 'ya' : 'tidak').PHP_EOL;
    echo 'hash cocok: '.($user && $plain !== '' && password_verify($plain, $user->password) ? 'COCOK' : 'TIDAK COCOK').PHP_EOL;
    exit;
}

// Hapus berkas pemasangan sementara.
if ($key === 'bersih') {
    foreach (['__cek.php', 'bootstrap.php', 'artisan.php'] as $file) {
        $path = __DIR__.'/'.$file;
        echo (is_file($path) ? (@unlink($path) ? 'hapus: ' : 'GAGAL hapus: ') : 'tidak ada: ').$file.PHP_EOL;
    }
    exit;
}

if (! isset($whitelist[$key])) {
    echo 'perintah tidak dikenal. pilihan: '.implode(', ', array_keys($whitelist)).PHP_EOL;
    exit;
}

[$command, $arguments] = $whitelist[$key];

require APP.'/vendor/autoload.php';
$laravel = require APP.'/bootstrap/app.php';
$laravel->make(Kernel::class)->bootstrap();

echo 'jalankan: '.$command.PHP_EOL;

try {
    Artisan::call($command, $arguments);
    echo trim(Artisan::output()).PHP_EOL;
    echo 'SELESAI'.PHP_EOL;
} catch (Throwable $e) {
    echo 'GAGAL: '.$e->getMessage().PHP_EOL;
}

if (isset($_GET['hapus'])) {
    @unlink(__FILE__);
    echo 'berkas ini dihapus.'.PHP_EOL;
}
