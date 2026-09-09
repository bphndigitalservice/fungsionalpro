<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Map old 'PASSED' data safely to your new baseline 'FAIR' (Cukup Memuaskan)
        DB::table('client_competences')
            ->where('completion_status', 'PASSED')
            ->update(['completion_status' => 'SATISFACTORY']);

        // 2. If you had any 'EXCELLENT' values, shift them to 'VERY_SATISFACTORY'
        DB::table('client_competences')
            ->where('completion_status', 'EXCELLENT')
            ->update(['completion_status' => 'VERY_SATISFACTORY']);

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // 3. Drop the old check constraint
        DB::statement('ALTER TABLE client_competences DROP CONSTRAINT IF EXISTS client_competences_completion_status_check');

        // 4. Apply the new strict 5-tier constraint
        DB::statement("ALTER TABLE client_competences ADD CONSTRAINT client_competences_completion_status_check CHECK (completion_status IN ('VERY_SATISFACTORY', 'SATISFACTORY', 'FAIR', 'LESS_SATISFACTORY', 'UNSATISFACTORY'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE client_competences DROP CONSTRAINT IF EXISTS client_competences_completion_status_check');

        // Restore the previous configuration just in case
        DB::statement("ALTER TABLE client_competences ADD CONSTRAINT client_competences_completion_status_check CHECK (completion_status IN ('PASSED', 'EXCELLENT', 'VERY_SATISFACTORY', 'SATISFACTORY', 'LESS_SATISFACTORY', 'UNSATISFACTORY'))");
    }
};