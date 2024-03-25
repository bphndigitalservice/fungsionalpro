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
        Schema::create('client_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('client_id');
            $table->string('name');
            $table->string('academic_title')->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->string('address');
            $table->string('phone_number');
            $table->string('photo')->nullable();
            $table->timestamps();

            $table->foreign('client_id')->on('clients')->references('id')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_identities');
    }
};
