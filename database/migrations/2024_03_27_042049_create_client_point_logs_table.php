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
        Schema::create('client_point_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('client_id');
            $table->foreignUlid('client_point_submissions_id');
            $table->float('point');
            $table->timestamps();

            $table->foreign('client_id')
                ->on('clients')
                ->references('id')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('client_point_submissions_id')
                ->on('client_point_submissions')
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
        Schema::dropIfExists('client_point_logs');
    }
};
