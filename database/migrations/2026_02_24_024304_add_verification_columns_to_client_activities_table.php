<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_activities', function (Blueprint $table) {

            // NULL = pending
            // TRUE = accepted
            // FALSE = rejected
            $table->boolean('is_verified')
                ->nullable()
                ->after('activity_file');

            // who verified
            $table->foreignId('verified_by')
                ->nullable()
                ->after('is_verified')
                ->constrained('users')
                ->nullOnDelete();

            // when verified
            $table->timestamp('verified_at')
                ->nullable()
                ->after('verified_by');

            // rejection notes
            $table->text('verification_note')
                ->nullable()
                ->after('verified_at');

        });
    }

    public function down(): void
    {
        Schema::table('client_activities', function (Blueprint $table) {

            $table->dropConstrainedForeignId('verified_by');

            $table->dropColumn([

                'is_verified',
                'verified_at',
                'verification_note',

            ]);

        });
    }
};