<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CRoleLevel;
use App\Models\MasterJf;

echo "Levels in CRoleLevel:\n";
$levels = CRoleLevel::distinct()->pluck('level');
foreach ($levels as $level) {
    echo "- '$level'\n";
    $ids = CRoleLevel::where('level', $level)->pluck('id');
    echo "  IDs: " . $ids->implode(', ') . "\n";
    $count = MasterJf::whereIn('c_role_level_id', $ids)->count();
    echo "  MasterJf count for this level: $count\n";
}
