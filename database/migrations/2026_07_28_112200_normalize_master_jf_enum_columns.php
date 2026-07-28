<?php

use App\Support\MasterJfEnumMapper;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        MasterJfEnumMapper::normalizeTable();
    }

    public function down(): void
    {
        // Irreversible data normalization
    }
};
