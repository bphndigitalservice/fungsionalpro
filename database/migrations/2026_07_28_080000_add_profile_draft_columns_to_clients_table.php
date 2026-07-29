<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('is_profile_draft')->default(false)->after('user_id');
            $table->json('profile_draft_data')->nullable()->after('is_profile_draft');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->string('nip')->nullable()->change();
            $table->unsignedBigInteger('c_role_id')->nullable()->change();
            $table->string('type')->nullable()->change();
            $table->string('agency_type')->nullable()->change();
            $table->unsignedBigInteger('agency_id')->nullable()->change();
        });

        DB::table('clients')->update(['is_profile_draft' => false]);
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['is_profile_draft', 'profile_draft_data']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->string('nip')->nullable(false)->change();
            $table->unsignedBigInteger('c_role_id')->nullable(false)->change();
            $table->string('type')->nullable(false)->change();
            $table->string('agency_type')->nullable(false)->change();
            $table->unsignedBigInteger('agency_id')->nullable(false)->change();
        });
    }
};
