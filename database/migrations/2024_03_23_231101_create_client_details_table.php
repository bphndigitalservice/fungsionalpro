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
        Schema::create('client_details', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('client_id');
            $table->date('tmt_cpns')->nullable();
            $table->date('tmt_jf')->nullable();
            $table->date('nomor_sk_jf')->nullable();
            $table->date('nomor_sk_jabatan')->nullable();
            $table->date('file_bukti_angkat')->nullable();
            $table->string('file_sk_cpns')->nullable();
            $table->string('file_sk_pns')->nullable();
            $table->string('file_sk_jf')->nullable();
            $table->date('file_kartu_pegawai')->nullable();

            $table->timestamps();

            $table->foreign('client_id')->on('clients')->references('id')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_details');
    }
};
