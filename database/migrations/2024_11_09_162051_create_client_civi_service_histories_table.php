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
        Schema::create('client_civil_service_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('client_id')
                ->constrained('clients')
                ->onDelete('cascade');
            $table->enum('type', ['cpns', 'pns', 'pppk']);
            $table->date('effective_date');
            $table->string('decree_number');
            $table->string('decree_file');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_civi_service_histories');
    }
};
