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
        Schema::create('client_competences', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('client_id');
            $table->string('title')->nullable();
            $table->date('start_period')->nullable();
            $table->date('end_period')->nullable();
            $table->string('institution')->nullable();
            $table->string('competence_file')->nullable();

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
        Schema::dropIfExists('client_competences');
    }
};
