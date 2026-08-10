<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update existing null values to false (0)
        DB::table('clients')->whereNull('is_verified')->update(['is_verified' => 0]);

        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('is_verified')->nullable()->default(null)->change();
        });
    }
};
