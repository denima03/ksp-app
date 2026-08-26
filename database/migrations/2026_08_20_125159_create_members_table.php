<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nomor_anggota')->unique();
            $table->string('nik', 16)->unique();
            $table->string('nama');
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->text('alamat');
            $table->string('no_hp');
            $table->string('jabatan');
            $table->string('status_kepegawaian'); // PNS, PPPK, Honorer, lainnya
            $table->decimal('gaji', 15, 2)->default(0);
            $table->decimal('tpp', 15, 2)->default(0);
            $table->decimal('sertifikasi', 15, 2)->default(0);
            $table->date('tanggal_masuk');
            $table->string('status')->default('aktif'); // aktif, tidak_aktif
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
