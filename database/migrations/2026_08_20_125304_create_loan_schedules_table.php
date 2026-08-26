<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->integer('angsuran_ke');
            $table->integer('bulan');
            $table->integer('tahun');
            $table->date('tanggal_jatuh_tempo');
            $table->decimal('pokok', 15, 2);
            $table->decimal('bunga', 15, 2);
            $table->decimal('jumlah_angsuran', 15, 2);
            $table->decimal('pokok_terbayar', 15, 2)->default(0);
            $table->decimal('bunga_terbayar', 15, 2)->default(0);
            $table->decimal('jumlah_terbayar', 15, 2)->default(0);
            $table->decimal('sisa_pokok', 15, 2);
            $table->string('status')->default('belum_bayar'); // belum_bayar, sebagian, lunas, terlambat
            $table->date('tanggal_bayar')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_schedules');
    }
};
