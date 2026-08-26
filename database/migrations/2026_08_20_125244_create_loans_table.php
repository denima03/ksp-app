<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loan_type_id')->constrained()->cascadeOnDelete();
            $table->string('nomor_pinjaman')->unique();
            $table->date('tanggal_pinjaman');
            $table->decimal('jumlah_pinjaman', 15, 2);
            $table->integer('tenor');
            $table->decimal('bunga', 5, 2)->default(2.0);
            $table->string('tipe_bunga')->default('flat');
            $table->string('payment_source');
            $table->string('status')->default('pengajuan');

            // Kolom Tambahan Top-Up
            $table->boolean('is_top_up')->default(false);
            $table->foreignId('previous_loan_id')->nullable()->constrained('loans')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
