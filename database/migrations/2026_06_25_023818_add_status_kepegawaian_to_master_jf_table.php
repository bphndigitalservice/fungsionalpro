<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_jf', function (Blueprint $table) {
            // Adding it as nullable ensures old records or missing data won't break
            $table->string('status_kepegawaian')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('master_jf', function (Blueprint $table) {
            $table->dropColumn('status_kepegawaian');
        });
    }
};