<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait EnsuresMasterJfApiSchema
{
    protected function ensureMasterJfApiSchema(): void
    {
        if (! Schema::hasTable('reg_provinces')) {
            Schema::create('reg_provinces', function (Blueprint $table) {
                $table->unsignedInteger('id')->primary();
                $table->string('name');
            });
        }

        if (Schema::hasTable('master_jf') && ! Schema::hasColumn('master_jf', 'province_id')) {
            Schema::table('master_jf', function (Blueprint $table) {
                $table->unsignedInteger('province_id')->nullable();
            });
        }
    }
}
