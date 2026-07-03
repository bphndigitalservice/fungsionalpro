<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('client_identities', function (Blueprint $table) {
            $table->string('title_prefix')
                ->nullable()
                ->before('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_identities', function (Blueprint $table) {
            $table->dropColumn('title_prefix');
        });
    }
};
