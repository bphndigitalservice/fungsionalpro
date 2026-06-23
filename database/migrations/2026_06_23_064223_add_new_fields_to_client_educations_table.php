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
        Schema::table('client_education', function (Blueprint $table) {
            $table->date('certificate_date')
                ->nullable()
                ->after('gpa');

            $table->string('title_inclusion_file')
                ->nullable()
                ->after('certificate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_education', function (Blueprint $table) {
            $table->dropColumn(['certificate_date', 'title_inclusion_file']);
        });
    }
};
