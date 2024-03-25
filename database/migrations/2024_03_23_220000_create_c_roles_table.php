<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('c_roles', function (Blueprint $table) {
            $table->id();
            $table->string('role_name')->default('Analis Hukum');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::table('clients', function (Blueprint $table){


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_roles');
    }
};
