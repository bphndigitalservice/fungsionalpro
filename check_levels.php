<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MasterJf;
$records = MasterJf::limit(10)->get();
foreach ($records as $r) {
    echo "ID: {$r->id}, LevelID: '{$r->c_role_level_id}'\n";
}
