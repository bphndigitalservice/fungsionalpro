<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ukom_applications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('target_c_role_id')->nullable();
            $table->string('status')->index();
            $table->string('nip');
            $table->string('nama');
            $table->string('type')->nullable();
            $table->string('agency_type')->nullable();
            $table->unsignedBigInteger('agency_id')->nullable();
            $table->boolean('claims_new_degree')->default(false);
            $table->text('rejection_reason')->nullable();
            $table->foreignId('instansi_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('instansi_reviewed_at')->nullable();
            $table->foreignId('admin_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('admin_reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('target_c_role_id')->references('id')->on('c_roles')->restrictOnDelete();
            $table->index(['user_id', 'status']);
            $table->index(['agency_type', 'agency_id']);
            $table->index('target_c_role_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ukom_applications');
    }
};
