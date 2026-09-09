<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MasterJf;

$records = MasterJf::query()
    ->limit(20)
    ->get(['jabatan']);

foreach ($records as $record) {
    echo "'{$record->jabatan}'\n";
}
