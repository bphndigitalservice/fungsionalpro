<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('c_role_level_id')->nullable()->change();
            $table->foreignId('reg_grade_id')->nullable()->change();
            $table->string('assignation_type')->nullable()->change();
            $table->string('status')->nullable()->default('active')->change();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Reverting back to NOT NULL if needed
            $table->foreignId('c_role_level_id')->nullable(false)->change();
            $table->foreignId('reg_grade_id')->nullable(false)->change();
            $table->string('assignation_type')->nullable(false)->change();
            $table->string('status')->nullable(false)->change();
        });
    }
};
