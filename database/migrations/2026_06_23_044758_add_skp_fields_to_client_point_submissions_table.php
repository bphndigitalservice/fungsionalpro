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
            // Adds the file tracking column
            $table->string('skp_file')
                ->nullable()
                ->after('point');

            // Adds the performance evaluation choice column
            $table->string('performance_predicate')
                ->nullable()
                ->after('skp_file');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_point_submissions', function (Blueprint $table) {
            $table->dropColumn(['skp_file', 'performance_predicate']);
        });
    }
};
