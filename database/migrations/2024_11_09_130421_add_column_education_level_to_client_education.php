<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('client_education', function (Blueprint $table) {
            $table->enum("level", ['elementary', 'junior_high', 'senior_high', 'diploma', 'bachelors', 'masters', 'doctorate'])
                ->nullable()
                ->default('bachelors')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_education', function (Blueprint $table) {
            //
        });
    }
};
