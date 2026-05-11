<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('master_clients', function (Blueprint $table) {
            $table->string('academic_title')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('master_clients', function (Blueprint $table) {
            $table->dropColumn('academic_title');
        });
    }
};