<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_accesses', function (Blueprint $table) {
            $table->string('entity_type')->nullable()->after('c_role_id');
            $table->unsignedBigInteger('entity_id')->nullable()->after('entity_type');          
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::table('admin_accesses', function (Blueprint $table) {
            $table->dropColumn(['entity_type', 'entity_id']);
        });
    }
};
