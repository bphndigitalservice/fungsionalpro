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
        Schema::create('c_role_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('c_role_id');
            $table->string('level');
            $table->timestamps();

            $table->foreign('c_role_id')->on('c_roles')->references('id')->onDelete('cascade')->onUpdate('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_role_levels');
    }
};
