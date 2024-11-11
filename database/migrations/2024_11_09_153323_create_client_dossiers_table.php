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
        Schema::create('client_dossiers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('client_document_type_id')
                ->constrained('client_document_types')
                ->onDelete('cascade');
            $table->foreignUlid('client_id')
                ->constrained('clients')
                ->onDelete('cascade');
            $table->string('document_number')
                ->nullable();
            $table->string('document_date')
                ->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_dossiers');
    }
};
