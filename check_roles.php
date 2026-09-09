<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CRole;
use App\Models\CRoleLevel;

echo "Roles:\n";
foreach (CRole::all() as $r) {
    echo "ID: {$r->id}, Name: '{$r->role_name}'\n";
    foreach (CRoleLevel::where('c_role_id', $r->id)->get() as $l) {
        echo "  LevelID: {$l->id}, LevelName: '{$l->level}'\n";
    }
}
