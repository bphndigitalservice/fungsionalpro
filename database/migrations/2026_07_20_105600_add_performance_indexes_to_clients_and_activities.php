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
        Schema::table('clients', function (Blueprint $table) {
            $table->index('is_verified');
            $table->index('reg_province_id');
            $table->index(['c_role_id', 'agency_type', 'agency_id'], 'clients_role_agency_index');
        });

        Schema::table('client_activities', function (Blueprint $table) {
            $table->index('is_verified');

            if (Schema::hasColumn('client_activities', 'client_id')) {
                $table->index(['client_id', 'is_verified'], 'client_activities_client_verified_index');
            }
        });
    }/

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['is_verified']);
            $table->dropIndex(['reg_province_id']);
            $table->dropIndex('clients_role_agency_index');
        });

        Schema::table('client_activities', function (Blueprint $table) {
            $table->dropIndex(['is_verified']);

            if (Schema::hasColumn('client_activities', 'client_id')) {
                $table->dropIndex('client_activities_client_verified_index');
            }
        });
    }
};
