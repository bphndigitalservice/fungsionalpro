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
        Schema::create('master_jf', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('nip')->unique();
            $table->string('gol_ruang')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('unit_kerja')->nullable();
            $table->string('instansi')->nullable();
            $table->string('pengangkatan')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_jfs');
    }
};
