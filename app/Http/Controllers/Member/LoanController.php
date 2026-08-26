<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use Illuminate\Support\Facades\Auth;

class LoanController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $memberId = $user?->member_id;

        if (! $memberId) {
            return redirect()->back()->with('error', 'Akun Anda tidak terhubung dengan data Anggota.');
        }

        $loans = Loan::with(['loanType', 'loanSchedules'])
            ->where('member_id', $memberId)
            ->latest()
            ->get();

        $totalSisaKewajiban = $loans->where('status', '!=', 'lunas')->sum(function ($loan) {
            return $loan->sisaNominal;
        });

        return view('member.loans.index', compact('loans', 'totalSisaKewajiban'));
    }

    public function show(Loan $loan)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if ($loan->member_id !== $user?->member_id) {
            abort(403, 'Anda tidak memiliki hak akses ke data pinjaman ini.');
        }

        $loan->load(['loanType', 'loanSchedules', 'loanPayments']);

        return view('member.loans.show', compact('loan'));
    }
}
