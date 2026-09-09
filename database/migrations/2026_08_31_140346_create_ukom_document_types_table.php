<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ukom_document_types', function (Blueprint $table) {
            $table->id();
            $table->string('pack')->index();
            $table->string('slug');
            $table->string('label');
            $table->boolean('is_required')->default(true);
            $table->boolean('required_if_claims_new_degree')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['pack', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ukom_document_types');
    }
};
