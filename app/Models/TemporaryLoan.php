<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemporaryLoan extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'tanggal_pinjam' => 'date',
        'tanggal_jatuh_tempo' => 'date',
        'jumlah_pinjaman' => 'float',
        'persen_bunga' => 'float',
        'jumlah_bunga' => 'float',
        'total_pelunasan' => 'float',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
