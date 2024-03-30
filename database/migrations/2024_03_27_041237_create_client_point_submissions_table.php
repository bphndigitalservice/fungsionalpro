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
        Schema::create('client_point_submissions', function (Blueprint $table) {
            $table->ulid('id')->unique();
            $table->foreignUlid('client_id');
            $table->foreignUlid('submission_bag_id');
            $table->string('submission_type', 128);
            $table->string('pak_number', 128);
            $table->float('point')->default(0);
            $table->string('pak_file');
            $table->string('x_skp2ak_number', 128)->nullable();
            $table->float('x_skp2ak_point')->nullable();
            $table->string('x_skp2ak_file', 128)->nullable();
            $table->string('x_accumulated_number', 128)->nullable();
            $table->float('x_accumulated_point')->nullable();
            $table->string('x_accumulated_file', 128)->nullable();
            $table->string('status', '50');
            $table->boolean('is_approved')->default(false);
            $table->string('verifier_note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('client_id')
                ->on('clients')
                ->references('id')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('submission_bag_id')
                ->on('client_point_submission_bags')
                ->references('id')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->index(['status', 'submission_type']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_point_submissions');
    }
};
