<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE master_clients DROP CONSTRAINT master_clients_pkey');

        DB::statement('ALTER TABLE master_clients DROP COLUMN id');

        DB::statement('ALTER TABLE master_clients ADD COLUMN id BIGSERIAL PRIMARY KEY');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE master_clients DROP CONSTRAINT master_clients_pkey');
        DB::statement('ALTER TABLE master_clients DROP COLUMN id');
        DB::statement('ALTER TABLE master_clients ADD COLUMN id VARCHAR(26) PRIMARY KEY');
    }
};
