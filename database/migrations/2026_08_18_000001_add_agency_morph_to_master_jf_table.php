<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('master_jf', 'agency_type')) {
            Schema::table('master_jf', function (Blueprint $table) {
                $table->nullableMorphs('agency');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('master_jf', 'agency_type')) {
            Schema::table('master_jf', function (Blueprint $table) {
                $table->dropMorphs('agency');
            });
        }
    }
};
