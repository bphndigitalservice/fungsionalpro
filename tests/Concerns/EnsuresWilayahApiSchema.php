<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait EnsuresWilayahApiSchema
{
    protected function ensureWilayahApiSchema(): void
    {
        if (! Schema::hasTable('reg_provinces')) {
            Schema::create('reg_provinces', function (Blueprint $table) {
                $table->unsignedInteger('id')->primary();
                $table->string('name');
            });
        }

        if (! Schema::hasTable('reg_regencies')) {
            Schema::create('reg_regencies', function (Blueprint $table) {
                $table->unsignedInteger('id')->primary();
                $table->unsignedInteger('province_id');
                $table->string('name');
            });
        }
    }
}
