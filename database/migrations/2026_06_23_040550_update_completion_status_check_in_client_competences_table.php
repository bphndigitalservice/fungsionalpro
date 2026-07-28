<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE client_competences DROP CONSTRAINT IF EXISTS client_competences_completion_status_check');

        DB::table('client_competences')
            ->whereNotIn('completion_status', ['PASSED', 'EXCELLENT', 'VERY_SATISFACTORY', 'SATISFACTORY', 'LESS_SATISFACTORY', 'UNSATISFACTORY'])
            ->update(['completion_status' => 'PASSED']);

        DB::statement("ALTER TABLE client_competences ADD CONSTRAINT client_competences_completion_status_check CHECK (completion_status IN ('PASSED', 'EXCELLENT', 'VERY_SATISFACTORY', 'SATISFACTORY', 'LESS_SATISFACTORY', 'UNSATISFACTORY'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE client_competences DROP CONSTRAINT client_competences_completion_status_check');

        DB::statement("ALTER TABLE client_competences ADD CONSTRAINT client_competences_completion_status_check CHECK (completion_status IN ('PASSED', 'FAILED', 'SATISFACTORY'))");
    }
};
