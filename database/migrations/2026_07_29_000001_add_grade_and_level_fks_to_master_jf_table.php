<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_jf', function (Blueprint $table) {
            if (! Schema::hasColumn('master_jf', 'reg_grade_id')) {
                $table->unsignedBigInteger('reg_grade_id')->nullable()->after('gol_ruang');
            }

            if (! Schema::hasColumn('master_jf', 'c_role_level_id')) {
                $table->unsignedBigInteger('c_role_level_id')->nullable()->after('c_role_id');
            }
        });

        $this->addForeignKeyIfMissing('reg_grade_id', 'reg_grades');
        $this->addForeignKeyIfMissing('c_role_level_id', 'c_role_levels');
    }

    public function down(): void
    {
        Schema::table('master_jf', function (Blueprint $table) {
            if (Schema::hasColumn('master_jf', 'reg_grade_id')) {
                try {
                    $table->dropForeign(['reg_grade_id']);
                } catch (\Throwable) {
                }
                $table->dropColumn('reg_grade_id');
            }

            if (Schema::hasColumn('master_jf', 'c_role_level_id')) {
                try {
                    $table->dropForeign(['c_role_level_id']);
                } catch (\Throwable) {
                }
                $table->dropColumn('c_role_level_id');
            }
        });
    }

    private function addForeignKeyIfMissing(string $column, string $referencesTable): void
    {
        try {
            Schema::table('master_jf', function (Blueprint $table) use ($column, $referencesTable) {
                $table->foreign($column)
                    ->references('id')
                    ->on($referencesTable)
                    ->nullOnDelete();
            });
        } catch (\Throwable) {
            // FK already exists (or driver cannot add it twice)
        }
    }
};
