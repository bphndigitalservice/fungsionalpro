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
        Schema::table("client_point_submissions",function (Blueprint $table){
            $table->foreignId('role_id')->nullable()->constrained('c_roles');
        });

        Schema::table("client_point_submission_files",function (Blueprint $table){
            $table->foreignId('role_id')->nullable()->constrained('c_roles');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
