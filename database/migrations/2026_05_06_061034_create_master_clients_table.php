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
        Schema::create('master_clients', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->unsignedBigInteger('c_role_id');
            $table->unsignedBigInteger('c_role_level_id');

            $table->string('name')->nullable();
            $table->string('nip')->unique();

            $table->unsignedBigInteger('reg_grade_id');

            $table->enum('type', [
                'central',
                'local_province',
                'local_regency',
            ]);

            $table->string('agency_type');
            $table->bigInteger('agency_id');

            $table->string('echelon_type')->nullable();
            $table->unsignedBigInteger('echelon_id')->nullable();
            $table->string('echelon_x_text')->nullable();

            $table->enum('status', [
                'active',
                'non_active_resign',
                'non_active_ctln',
                'non_active_study_leave',
                'non_active_external_assignment',
                'non_active_doesnt_meet_role_requirement',
            ])->default('active');

            $table->string('assignation_type');

            $table->timestamps();

            // Indexes
            $table->index('nip');
            $table->index(['status', 'assignation_type']);
            $table->index(['agency_id', 'echelon_id']);

            // Foreign Keys
            $table->foreign('c_role_id')
                ->references('id')
                ->on('c_roles')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
            $table->foreign('reg_grade_id')
                ->references('id')
                ->on('reg_grades')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            */

            /*
            $table->foreign('c_role_level_id')
                ->references('id')
                ->on('c_role_levels')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            */
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_clients');
    }
};