<?php

/**
 * Skrip migrasi data: SQLite (database/database.sqlite) -> MySQL (.env).
 *
 * Jalankan sekali setelah `php artisan migrate` pada database MySQL kosong:
 *   php database/scripts/migrate_sqlite_to_mysql.php
 *
 * Skrip menyalin seluruh baris apa adanya (termasuk UUID & timestamp) sehingga
 * relasi antar tabel tetap utuh dan password pengguna tidak berubah.
 */

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$sqlitePath = database_path('database.sqlite');

if (! file_exists($sqlitePath)) {
    fwrite(STDERR, "File SQLite tidak ditemukan: {$sqlitePath}\n");
    exit(1);
}

$sqlite = new PDO('sqlite:'.$sqlitePath);
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Urutan penting: tabel induk lebih dulu agar foreign key tidak dilanggar.
$tables = [
    'roles',
    'users',
    'employees',
    'shops',
    'spks',
    'cash_advances',
    'payrolls',
    'personal_access_tokens',
];

DB::statement('SET FOREIGN_KEY_CHECKS=0');

foreach ($tables as $table) {
    $rows = $sqlite->query("SELECT * FROM {$table}")->fetchAll(PDO::FETCH_ASSOC);

    DB::table($table)->truncate();

    if ($rows === []) {
        echo str_pad($table, 24)."0 baris\n";

        continue;
    }

    foreach (array_chunk($rows, 100) as $chunk) {
        DB::table($table)->insert($chunk);
    }

    echo str_pad($table, 24).count($rows)." baris\n";
}

DB::statement('SET FOREIGN_KEY_CHECKS=1');

echo "\nSelesai. Cek jumlah baris:\n";
foreach ($tables as $table) {
    echo str_pad($table, 24).DB::table($table)->count()."\n";
}
