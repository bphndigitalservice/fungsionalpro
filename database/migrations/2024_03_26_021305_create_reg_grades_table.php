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
        Schema::create('reg_grades', function (Blueprint $table) {
            $table->id();
            $table->string('grade_name');
            $table->string('grade_code');
            $table->timestamps();
        });


        // Schema::table('clients', function (Blueprint $table) {
        //     $table->foreign('reg_grade_id')
        //         ->on('reg_grades')
        //         ->references('id')
        //         ->onDelete('set null')
        //         ->onUpdate('cascade');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reg_grades');
    }
};
