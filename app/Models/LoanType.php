<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanType extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'bunga_per_tahun' => 'float',
        'maksimal_tenor' => 'integer',
        'maksimal_pinjaman' => 'float',
        'is_active' => 'boolean',
    ];
}
