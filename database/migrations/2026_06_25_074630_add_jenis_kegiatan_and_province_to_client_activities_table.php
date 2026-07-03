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
        Schema::table('client_activities', function (Blueprint $table) {
            $table->string('jenis_kegiatan')->nullable()->after('title');
            
            // Assuming your reg_provinces table uses an unsigned big integer or similar for its ID
            $table->foreignId('reg_province_id')
                ->nullable()
                ->after('jenis_kegiatan')
                ->constrained('reg_provinces')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_activities', function (Blueprint $table) {
            $table->dropForeign(['reg_province_id']);
            $table->dropColumn(['jenis_kegiatan', 'reg_province_id']);
        });
    }
};