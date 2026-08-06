<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('master_jf', 'provinsi')) {
            return;
        }

        Schema::table('master_jf', function (Blueprint $table) {
            $table->string('provinsi')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_jf', function (Blueprint $table) {
            $table->dropColumn('provinsi');
        });
    }
};
