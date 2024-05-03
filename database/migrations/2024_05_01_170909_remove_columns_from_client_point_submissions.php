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
        Schema::table('client_point_submissions', function (Blueprint $table) {
            $table->string('applied_rule')->default('v1')->index();
            $table->removeColumn('submission_bag_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_point_submissions', function (Blueprint $table) {
            //
        });
    }
};
