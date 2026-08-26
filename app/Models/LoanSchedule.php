<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'angsuran_ke',
        'bulan',
        'tahun',
        'tanggal_jatuh_tempo',
        'pokok',
        'bunga',
        'jumlah_angsuran',
        'pokok_terbayar',
        'bunga_terbayar',
        'jumlah_terbayar',
        'sisa_pokok',
        'status',
        'tanggal_bayar',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_jatuh_tempo' => 'date',
            'tanggal_bayar' => 'date',
            'angsuran_ke' => 'integer',
            'bulan' => 'integer',
            'tahun' => 'integer',
            'pokok' => 'decimal:2',
            'bunga' => 'decimal:2',
            'jumlah_angsuran' => 'decimal:2',
            'pokok_terbayar' => 'decimal:2',
            'bunga_terbayar' => 'decimal:2',
            'jumlah_terbayar' => 'decimal:2',
            'sisa_pokok' => 'decimal:2',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function loanPayments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }
}
