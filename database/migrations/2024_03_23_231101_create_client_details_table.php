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
        Schema::create('client_details', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('client_id');
            $table->date('sk_cpns_tmt')->nullable();
            $table->string('sk_cpns_file')->nullable();
            $table->string('sk_pns_file')->nullable();
            $table->string('sk_latest_jf_no')->nullable();
            $table->date('sk_latest_jf_tmt')->nullable();
            $table->string('sk_latest_jf_file')->nullable();
            $table->string('sk_latest_grade_no')->nullable();
            $table->date('sk_latest_grade_tmt')->nullable();
            $table->string('sk_latest_grade_file')->nullable();
            $table->string('file_assignation_evd')->nullable();
            $table->string('file_employee_card')->nullable();

            $table->timestamps();

            $table->foreign('client_id')
                ->on('clients')
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
        Schema::dropIfExists('client_details');
    }
};
