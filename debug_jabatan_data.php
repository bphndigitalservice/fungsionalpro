<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MasterJf;

$records = MasterJf::query()
    ->where('jabatan', 'LIKE', '%Penyuluh%')
    ->limit(5)
    ->get(['jabatan']);

echo "Penyuluh Hukum examples:\n";
foreach ($records as $record) {
    echo "'{$record->jabatan}'\n";
}

$records = MasterJf::query()
    ->where('jabatan', 'LIKE', '%Analis%')
    ->limit(5)
    ->get(['jabatan']);

echo "\nAnalis Hukum examples:\n";
foreach ($records as $record) {
    echo "'{$record->jabatan}'\n";
}
