<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'tanggal_pinjaman' => 'date',
        'jumlah_pinjaman' => 'float',
        'bunga' => 'float',
        'tenor' => 'integer',
        'is_top_up' => 'boolean',
        'sisa_pinjaman_lama' => 'float',
        'pencairan_bersih' => 'float',
    ];

    public function previousLoan(): BelongsTo
    {
        return $this->belongsTo(Loan::class, 'previous_loan_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function loanType(): BelongsTo
    {
        return $this->belongsTo(LoanType::class);
    }

    public function loanSchedules(): HasMany
    {
        return $this->hasMany(LoanSchedule::class);
    }

    public function loanPayments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function getNomorPinjamanDenganNamaAttribute(): string
    {
        return "{$this->nomor_pinjaman} - " . ($this->member ? $this->member->nama : 'Umum');
    }
}
