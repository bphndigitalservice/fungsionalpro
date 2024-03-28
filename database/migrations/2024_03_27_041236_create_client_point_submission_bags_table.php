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
        Schema::create('client_point_submission_bags', function (Blueprint $table) {
            $table->ulid('id')->unique();
            $table->string('label');
            $table->dateTime('date_start');
            $table->dateTime('date_end');
            $table->boolean('is_enabled')->default(false);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_enabled']);

            $table->foreign('created_by')
                ->on('users')
                ->references('id')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('updated_by')
                ->on('users')
                ->references('id')
                ->onDelete('set null')
                ->onUpdate('cascade');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_point_submission_verifications');
    }
};
