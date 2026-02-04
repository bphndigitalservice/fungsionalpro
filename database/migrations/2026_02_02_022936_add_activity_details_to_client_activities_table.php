<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_activities', function (Blueprint $table) {
            $table->jsonb('activity_details')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('client_activities', function (Blueprint $table) {
            $table->dropColumn('activity_details');
        });
    }
};