<?php

use App\Support\RegGradeResolver;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        RegGradeResolver::backfillMasterJf();
    }

    public function down(): void
    {
        // Irreversible data backfill
    }
};
