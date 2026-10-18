<?php

declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;

/**
 * deploy.php — penerima rilis dari GitHub Actions.
 *
 * Dipakai untuk hosting shared/cPanel yang TIDAK punya akses SSH (mis. Rumahweb Small).
 * Alurnya:
 *   1. GitHub Actions membangun aplikasi (composer install --no-dev) lalu mengunggah
 *      release.zip lewat FTP ke folder ../deploy/.
 *   2. Workflow memanggil berkas ini lewat HTTPS dengan token.
 *   3. Berkas ini mengekstrak zip ke folder aplikasi, menjalankan migrasi & cache
 *      lewat kernel Laravel (tanpa shell_exec, karena biasanya dimatikan di shared hosting).
 *
 * PEMASANGAN (sekali saja, lewat File Manager cPanel):
 *   - Letakkan berkas ini di  public_html/deploy.php
 *   - Buat folder           ~/deploy/            (di luar public_html)
 *   - Buat berkas           ~/deploy/token.txt   berisi token acak panjang (satu baris)
 *   - Pastikan folder       ~/app/               ada (root Laravel)
 */

// ---------------------------------------------------------------------------
// Konfigurasi
// ---------------------------------------------------------------------------
const APP_PATH = __DIR__.'/../app';           // root aplikasi Laravel
const ZIP_PATH = __DIR__.'/../deploy/release.zip';
const TOKEN_FILE = __DIR__.'/../deploy/token.txt';
const LOG_FILE = __DIR__.'/../deploy/deploy.log';

const REQUIRED_DIRS = [
    'storage/app/public',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache',
];

// ---------------------------------------------------------------------------
// Pembantu
// ---------------------------------------------------------------------------
// Jangan tampilkan warning PHP ke respons: klien mengharapkan JSON bersih.
ini_set('display_errors', '0');
error_reporting(E_ALL);

function respond(int $code, array $data): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

function write_log(string $message): void
{
    @file_put_contents(LOG_FILE, '['.date('c').'] '.$message.PHP_EOL, FILE_APPEND);
}

// ---------------------------------------------------------------------------
// 1. Autentikasi token
// ---------------------------------------------------------------------------
$token = (string) ($_GET['token'] ?? $_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? '');
$expected = is_file(TOKEN_FILE) ? trim((string) file_get_contents(TOKEN_FILE)) : '';

if ($expected === '' || $token === '' || ! hash_equals($expected, $token)) {
    write_log('token tidak valid dari '.($_SERVER['REMOTE_ADDR'] ?? '?'));
    respond(403, ['ok' => false, 'message' => 'Token tidak valid.']);
}

// ---------------------------------------------------------------------------
// 2. Ekstrak rilis
// ---------------------------------------------------------------------------
if (! is_file(ZIP_PATH)) {
    respond(404, ['ok' => false, 'message' => 'release.zip tidak ditemukan di folder deploy.']);
}

if (! is_dir(APP_PATH) && ! mkdir(APP_PATH, 0755, true) && ! is_dir(APP_PATH)) {
    respond(500, ['ok' => false, 'message' => 'Folder aplikasi tidak bisa dibuat.']);
}

$zip = new ZipArchive;

if ($zip->open(ZIP_PATH) !== true) {
    respond(500, ['ok' => false, 'message' => 'release.zip rusak atau tidak bisa dibuka.']);
}

$fileCount = $zip->numFiles;
$extracted = $zip->extractTo(APP_PATH);
$zip->close();

if (! $extracted) {
    respond(500, ['ok' => false, 'message' => 'Gagal mengekstrak release.zip.']);
}

@unlink(ZIP_PATH);

// Folder yang wajib ada (zip tidak selalu membawa folder kosong).
foreach (REQUIRED_DIRS as $dir) {
    $path = APP_PATH.'/'.$dir;

    if (! is_dir($path)) {
        @mkdir($path, 0755, true);
    }
}

// Bersihkan cache opcache supaya berkas baru langsung terpakai.
if (function_exists('opcache_reset')) {
    @opcache_reset();
}

write_log("rilis diekstrak ({$fileCount} berkas)");

// ---------------------------------------------------------------------------
// 3. Jalankan perintah artisan lewat kernel Laravel
// ---------------------------------------------------------------------------
$results = [];

try {
    require APP_PATH.'/vendor/autoload.php';

    /** @var Application $app */
    $app = require APP_PATH.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();

    foreach ([
        'migrate' => ['--force' => true],
        'app:init-admin' => [],
        'optimize' => [],
    ] as $command => $arguments) {
        try {
            Artisan::call($command, $arguments);
            $output = trim(Artisan::output());
            $results[$command] = $output !== '' ? $output : 'selesai';
        } catch (Throwable $e) {
            $results[$command] = 'GAGAL: '.$e->getMessage();
            write_log("{$command} gagal: ".$e->getMessage());
        }
    }
} catch (Throwable $e) {
    $reason = str_replace(APP_PATH, '{app}', $e->getMessage());
    write_log('bootstrap gagal: '.$e->getMessage());

    respond(500, [
        'ok' => false,
        'message' => 'Berkas sudah diekstrak, tetapi Laravel gagal dijalankan: '.$reason,
        'files' => $fileCount,
    ]);
}

write_log('deploy selesai');

respond(200, [
    'ok' => true,
    'files' => $fileCount,
    'commands' => $results,
]);
