<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('verifier_bphn_scopes')) {
            DB::table('verifier_accesses')
                ->where('entity_type', 'App\\Models\\VerifierBphnScope')
                ->update(['entity_type' => null, 'entity_id' => null]);

            Schema::dropIfExists('verifier_bphn_scopes');
        }

        Schema::table('verifier_accesses', function (Blueprint $table) {
            $table->string('entity_type')->nullable()->change();
            $table->unsignedBigInteger('entity_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('verifier_accesses', function (Blueprint $table) {
            $table->string('entity_type')->nullable(false)->change();
            $table->unsignedBigInteger('entity_id')->nullable(false)->change();
        });
    }
};
