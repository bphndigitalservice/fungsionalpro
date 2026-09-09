<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ukom_application_documents', function (Blueprint $table) {
            $table->id();
            $table->ulid('ukom_application_id');
            $table->foreignId('ukom_document_type_id')->constrained('ukom_document_types')->cascadeOnDelete();
            $table->string('file_path');
            $table->timestamps();

            $table->foreign('ukom_application_id')
                ->references('id')
                ->on('ukom_applications')
                ->cascadeOnDelete();

            $table->unique(['ukom_application_id', 'ukom_document_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ukom_application_documents');
    }
};
