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
        Schema::create('client_positions', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('c_role_level_id')
                ->constrained('c_role_levels')
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
        Schema::dropIfExists('client_positions');
    }
};
