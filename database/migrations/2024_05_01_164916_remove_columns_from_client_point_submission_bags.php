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
        Schema::table('client_point_submission_bags', function (Blueprint $table) {
            $table->removeColumn('date_start');
            $table->removeColumn('date_end');
            $table->removeColumn('created_by');
            $table->removeColumn('updated_by');
            $table->json('rules')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_point_submission_bags', function (Blueprint $table) {
            //
        });
    }
};
