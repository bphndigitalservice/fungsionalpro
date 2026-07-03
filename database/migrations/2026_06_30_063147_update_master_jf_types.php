<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\MasterJf;
use App\Services\ClientMatchingService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        MasterJf::all()->each(function (MasterJf $master) {
            [$type, $model] = ClientMatchingService::determineAgencyInfo($master->instansi ?? '', $master->unit_kerja ?? '');
            $master->update(['type' => $type]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
