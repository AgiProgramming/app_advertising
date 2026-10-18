<?php

use Composer\Console\Application;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * bootstrap.php — deploy pertama untuk hosting TANPA SSH (cPanel).
 *
 * Kenapa ada berkas ini: pada deploy pertama belum ada `vendor/`, jadi
 * `deploy.php` (yang butuh Laravel untuk migrasi) belum bisa dipakai.
 * Berkas ini menarik kode dari GitHub, memasang dependensi dengan Composer
 * yang dijalankan DI DALAM PHP (tanpa shell_exec), lalu menjalankan migrasi.
 *
 * Cara pakai:
 *   1. Unggah ke public_html/bootstrap.php lewat File Manager.
 *   2. Buka https://domain/bootstrap.php sekali saja.
 *   3. Berkas ini menghapus dirinya sendiri setelah selesai.
 *
 * Setelah itu, deploy berikutnya memakai alur normal (GitHub Actions -> deploy.php).
 */
set_time_limit(0);
ini_set('memory_limit', '512M');
ini_set('display_errors', '0');
header('Content-Type: text/plain; charset=utf-8');

const HOME = '/home/gonn2842';
const APP = HOME.'/app';
const REPO = 'AgiProgramming/app_advertising';

function step(string $message): void
{
    echo $message.PHP_EOL;
    flush();
}

/**
 * Hapus folder rekursif tanpa shell (exec/shell_exec dimatikan di shared hosting).
 */
function remove_dir(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
    }

    @rmdir($dir);
}

try {
    step('1. unduh kode dari GitHub...');
    $tgz = HOME.'/deploy/src.tar.gz';
    $ctx = stream_context_create(['http' => ['timeout' => 120, 'user_agent' => 'bootstrap']]);
    $data = @file_get_contents('https://codeload.github.com/'.REPO.'/tar.gz/refs/heads/main', false, $ctx);

    if ($data === false || strlen($data) < 1000) {
        throw new RuntimeException('gagal unduh repo');
    }

    file_put_contents($tgz, $data);
    step('   '.round(strlen($data) / 1024).' KB');

    step('2. ekstrak...');
    $tmp = HOME.'/deploy/src';
    remove_dir($tmp);
    @mkdir($tmp, 0755, true);
    (new PharData($tgz))->extractTo($tmp, null, true);
    $inner = glob($tmp.'/*', GLOB_ONLYDIR)[0] ?? null;

    if (! $inner) {
        throw new RuntimeException('isi tar tidak dikenali');
    }

    step('3. salin ke app/ ...');
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($inner, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    $copied = 0;

    foreach ($iterator as $item) {
        $relative = substr($item->getPathname(), strlen($inner) + 1);
        $destination = APP.'/'.$relative;

        if ($item->isDir()) {
            if (! is_dir($destination)) {
                @mkdir($destination, 0755, true);
            }

            continue;
        }

        if (! is_dir(dirname($destination))) {
            @mkdir(dirname($destination), 0755, true);
        }

        copy($item->getPathname(), $destination);
        $copied++;
    }

    step('   '.$copied.' berkas');

    step('4. siapkan composer...');
    $pharFile = HOME.'/deploy/composer.phar';

    if (! is_file($pharFile)) {
        $composer = @file_get_contents('https://getcomposer.org/download/latest-stable/composer.phar', false, $ctx);

        if ($composer === false) {
            throw new RuntimeException('gagal unduh composer');
        }

        file_put_contents($pharFile, $composer);
    }

    step('   '.round(filesize($pharFile) / 1024).' KB');
    $source = HOME.'/deploy/composer-src';

    if (! is_dir($source.'/vendor')) {
        @mkdir($source, 0755, true);
        (new Phar($pharFile))->extractTo($source, null, true);
    }

    require $source.'/vendor/autoload.php';

    step('5. composer install --no-dev ...');
    putenv('COMPOSER_HOME='.HOME.'/.composer');
    putenv('COMPOSER_NO_INTERACTION=1');
    chdir(APP);

    $composerApp = new Application;
    $composerApp->setAutoExit(false);
    $input = new ArrayInput([
        'command' => 'install',
        '--no-dev' => true,
        '--no-scripts' => true,
        '--optimize-autoloader' => true,
        '--no-interaction' => true,
        '--no-progress' => true,
    ]);
    $output = new BufferedOutput;
    $exitCode = $composerApp->run($input, $output);
    step('   exit '.$exitCode);

    if ($exitCode !== 0) {
        step(substr($output->fetch(), -700));

        throw new RuntimeException('composer gagal');
    }

    step('6. artisan...');
    require APP.'/vendor/autoload.php';
    $laravel = require APP.'/bootstrap/app.php';
    $laravel->make(Kernel::class)->bootstrap();

    $commands = [
        'package:discover' => [],
        'migrate' => ['--force' => true],
        'app:init-admin' => [],
        'optimize' => [],
    ];

    foreach ($commands as $command => $arguments) {
        try {
            Artisan::call($command, $arguments);
            $result = trim(Artisan::output());
            step('   ['.$command.'] '.($result !== '' ? substr($result, 0, 250) : 'ok'));
        } catch (Throwable $e) {
            step('   ['.$command.'] GAGAL: '.$e->getMessage());
        }
    }

    remove_dir($tmp);
    @unlink($tgz);
    step('SELESAI');
    @unlink(__FILE__);
} catch (Throwable $e) {
    step('ERROR: '.$e->getMessage());
}
