<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            if (!Schema::hasColumn('loans', 'is_top_up')) {
                $table->boolean('is_top_up')->default(false)->after('tenor');
            }
            if (!Schema::hasColumn('loans', 'previous_loan_id')) {
                $table->foreignId('previous_loan_id')->nullable()->constrained('loans')->nullOnDelete()->after('is_top_up');
            }
            if (!Schema::hasColumn('loans', 'sisa_pinjaman_lama')) {
                $table->decimal('sisa_pinjaman_lama', 15, 2)->default(0)->after('previous_loan_id');
            }
            if (!Schema::hasColumn('loans', 'pencairan_bersih')) {
                $table->decimal('pencairan_bersih', 15, 2)->default(0)->after('sisa_pinjaman_lama');
            }
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['previous_loan_id']);
            $table->dropColumn(['is_top_up', 'previous_loan_id', 'sisa_pinjaman_lama', 'pencairan_bersih']);
        });
    }
};
