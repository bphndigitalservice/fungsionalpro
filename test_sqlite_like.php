<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$jabatan = 'PENYULUH HUKUM AHLI PERTAMA';
$match = 'Ahli Pertama';

$results = DB::table('master_jf')
    ->where('jabatan', 'LIKE', '%' . $match)
    ->get();

echo "Found " . $results->count() . " records for '{$match}'\n";
foreach ($results as $r) {
    echo "Found: '{$r->jabatan}'\n";
}
