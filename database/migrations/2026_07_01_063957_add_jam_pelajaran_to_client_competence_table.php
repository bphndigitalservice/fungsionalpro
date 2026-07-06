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
        // Replace 'client_competences' with your actual table name if it's different
        Schema::table('client_competences', function (Blueprint $table) {
            $table->integer('jam_pelajaran')
                ->default(0)
                ->nullable()
                ->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_competences', function (Blueprint $table) {
            $table->dropColumn('jam_pelajaran');
        });
    }
};
