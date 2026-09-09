<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ukom_applications', function (Blueprint $table) {
            $table->string('current_jabatan')->nullable()->after('nama');
        });
    }

    public function down(): void
    {
        Schema::table('ukom_applications', function (Blueprint $table) {
            $table->dropColumn('current_jabatan');
        });
    }
};
