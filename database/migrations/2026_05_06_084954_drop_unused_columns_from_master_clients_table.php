<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_clients', function (Blueprint $table) {
            $table->dropColumn(['agency_type', 'echelon_type', 'echelon_x_text']);
        });
    }

    public function down(): void
    {
        Schema::table('master_clients', function (Blueprint $table) {
            $table->string('agency_type')->nullable();
            $table->string('echelon_type')->nullable();
            $table->text('echelon_x_text')->nullable();
        });
    }
};