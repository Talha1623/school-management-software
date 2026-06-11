<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = [
    'staff',
    'staff_attendances',
    'sessions',
    'exam_settings',
    'particular_test_grades',
    'parent_complaints',
    'timetables',
];

$sql = '';

foreach ($tables as $table) {
    try {
        $rows = Illuminate\Support\Facades\DB::select("SHOW CREATE TABLE `{$table}`");
    } catch (Throwable $e) {
        continue;
    }

    if (empty($rows)) {
        continue;
    }

    $createMap = (array) $rows[0];
    $createSql = $createMap['Create Table'] ?? array_values($createMap)[1] ?? null;

    if (!$createSql) {
        continue;
    }

    $sql .= "DROP TABLE IF EXISTS `{$table}`;" . PHP_EOL;
    $sql .= $createSql . ';' . PHP_EOL . PHP_EOL;
}

$outputPath = __DIR__ . '/../storage/app/tenant_core_tables.sql';
file_put_contents($outputPath, $sql);

echo $outputPath . PHP_EOL;
