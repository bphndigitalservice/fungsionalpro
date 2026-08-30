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

        DB::statement('ALTER TABLE client_competences DROP CONSTRAINT IF EXISTS client_competences_category_check');

        DB::statement("ALTER TABLE client_competences ADD CONSTRAINT client_competences_category_check CHECK (category IN ('PROMOTION_TRAINING', 'TECHNICAL_TRAINING', 'OTHER_TRAINING'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE client_competences DROP CONSTRAINT IF EXISTS client_competences_category_check');

        DB::statement("ALTER TABLE client_competences ADD CONSTRAINT client_competences_category_check CHECK (category IN ('PROMOTION_TRAINING', 'TECHNICAL_TRAINING'))");
    }
};
