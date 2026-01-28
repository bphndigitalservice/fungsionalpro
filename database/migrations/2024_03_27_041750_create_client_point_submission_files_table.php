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
        Schema::create('client_point_submission_files', function (Blueprint $table) {
            $table->id();

            $table->foreignUlid('client_point_submission_id')
                ->constrained('client_point_submissions')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUlid('requisite_spec_id');
            $table->string('path');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_point_submission_files');
    }
};
