<?php

use App\Support\MasterJfAgencyResolver;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        MasterJfAgencyResolver::backfillMasterJf();
    }

    public function down(): void
    {
        // Irreversible data backfill
    }
};
