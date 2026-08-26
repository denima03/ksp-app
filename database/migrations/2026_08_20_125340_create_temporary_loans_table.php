<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temporary_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('nomor_pinjaman')->unique();
            $table->date('tanggal_pinjam');
            $table->date('tanggal_jatuh_tempo');
            $table->decimal('jumlah_pinjaman', 15, 2);
            $table->decimal('persen_bunga', 5, 2)->default(2.0);
            $table->decimal('jumlah_bunga', 15, 2);
            $table->decimal('total_pelunasan', 15, 2);
            $table->string('status')->default('aktif');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_loans');
    }
};
