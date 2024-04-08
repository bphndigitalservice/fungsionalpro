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
        Schema::create('v_client_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('client_id');
            $table->string('client_notes');
            $table->string('verifier_notes');
            $table->timestamps();

            $table->foreign('client_id')
                ->on('clients')
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
        Schema::dropIfExists('v_client_notes');
    }
};
