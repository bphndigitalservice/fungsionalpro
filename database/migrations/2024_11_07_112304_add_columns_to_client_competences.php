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
        Schema::table('client_competences', function (Blueprint $table) {
            $table->string('certificate_number')->nullable()->default('0');
            $table->enum('category', ['PROMOTION_TRAINING', 'TECHNICAL_TRAINING'])->default('TECHNICAL_TRAINING')->index()->nullable();
            $table->enum('completion_status', ['PASSED', 'FAILED', 'SATISFACTORY'])->index()->nullable();
            $table->foreignId('promotion_training_level_id')->nullable()->constrained('c_role_levels')->nullOnDelete()->nullOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_competences', function (Blueprint $table) {
            $table->dropColumn('certificate_number');
            $table->dropColumn('category');
            $table->dropColumn('completion_status');
            $table->dropForeign(['promotion_training_level_id']);
            $table->dropColumn('promotion_training_level_id');
        });
    }
};
