<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MasterJf;
echo "Total records: " . MasterJf::count() . "\n";
$first = MasterJf::first();
echo "First record: " . ($first ? "ID: {$first->id}, LevelID: {$first->c_role_level_id}" : "None") . "\n";
