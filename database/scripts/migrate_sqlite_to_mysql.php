<?php

require __DIR__.'/../../vendor/autoload.php';

$app = require_once __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

Config::set('database.connections.sqlite_src', [
    'driver' => 'sqlite',
    'database' => __DIR__.'/../../database/database.sqlite',
    'prefix' => '',
]);

$tables = [
    'classes', 'subjects', 'users', 'student_subject',
    'attendances', 'grades', 'password_reset_tokens', 'sessions',
];

$zeroDates = ['0000-00-00 00:00:00', '0000-00-00'];

DB::statement('SET FOREIGN_KEY_CHECKS=0');

foreach ($tables as $table) {
    $src = DB::connection('sqlite_src');
    $exists = $src->select("SELECT name FROM sqlite_master WHERE type='table' AND name = ?", [$table]);
    if ($exists === []) {
        echo str_pad($table, 24)."skip (not in sqlite)\n";
        continue;
    }

    $columns = array_map(
        fn (object $col) => $col->name,
        $src->select(sprintf('PRAGMA table_info(%s)', $table))
    );

    $rows = $src->table($table)->get();
    DB::table($table)->delete();

    $inserted = 0;
    foreach ($rows->chunk(200) as $chunk) {
        $payload = [];
        foreach ($chunk as $row) {
            $values = [];
            foreach ($columns as $column) {
                $value = $row->{$column} ?? null;
                if (is_string($value) && in_array($value, $zeroDates, true)) {
                    $value = null;
                }
                $values[$column] = $value;
            }
            $payload[] = $values;
        }
        if ($payload !== []) {
            DB::table($table)->insert($payload);
            $inserted += count($payload);
        }
    }

    $maxId = in_array('id', $columns, true) ? DB::table($table)->max('id') : null;
    if ($maxId !== null) {
        DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = ".((int) $maxId + 1));
    }

    echo str_pad($table, 24)."{$inserted} rows\n";
}

DB::statement('SET FOREIGN_KEY_CHECKS=1');

echo "\nKiểm tra lại phía MySQL:\n";
foreach ($tables as $table) {
    try {
        echo str_pad($table, 24).DB::table($table)->count()."\n";
    } catch (Throwable $e) {
        echo str_pad($table, 24)."n/a\n";
    }
}
