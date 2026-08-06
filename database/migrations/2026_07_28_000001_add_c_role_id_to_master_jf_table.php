<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('master_jf', 'c_role_id')) {
            return;
        }

        Schema::table('master_jf', function (Blueprint $table) {
            $table->foreignId('c_role_id')
                ->nullable()
                ->after('jabatan')
                ->constrained('c_roles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('master_jf', function (Blueprint $table) {
            $table->dropConstrainedForeignId('c_role_id');
        });
    }
};
