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
        Schema::create('admin_accesses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('c_role_id');
            $table->timestamps();

            $table->foreign('user_id')
                ->on('users')
                ->references('id')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('c_role_id')
                ->on('c_roles')
                ->references('id')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_accesses');
    }
};
