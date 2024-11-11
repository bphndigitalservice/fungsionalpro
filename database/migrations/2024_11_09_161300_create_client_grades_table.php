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
        Schema::create('client_grades', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('reg_grade_id')
                ->constrained('reg_grades')
                ->onDelete('cascade');
            $table->enum('type', ['current', 'past']);
            $table->date('effective_date');
            $table->string('decree_number');
            $table->text('decree_file');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_grades');
    }
};
