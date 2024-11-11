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
        Schema::create('clients', function (Blueprint $table) {
            $table->ulid('id')->unique();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('c_role_id');
            $table->unsignedBigInteger('c_role_level_id');
            $table->string('nip')->unique();
            $table->unsignedBigInteger('reg_grade_id');
            $table->enum('type', ['central', 'local_province', 'local_regency']);
            $table->string('agency_type');
            $table->bigInteger('agency_id');
            $table->string('echelon_type')->nullable();
            $table->unsignedBigInteger('echelon_id')->nullable();
            $table->string('echelon_x_text')->nullable();
            $table->enum('status', ['active', 'non_active_resign', 'non_active_ctln', 'non_active_study_leave', 'non_active_external_assignment', 'non_active_doesnt_meet_role_requirement'])->default('active');
            $table->string('assignation_type');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['nip', 'status', 'assignation_type', 'agency_id', 'echelon_type', 'echelon_id', 'echelon_x_text']);
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
        Schema::dropIfExists('clients');
    }
};
