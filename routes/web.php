<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Member\LoanController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->prefix('member')->name('member.')->group(function () {
    // Daftar semua pinjaman (Reguler & Sementara)
    Route::get('/pinjaman', [LoanController::class, 'index'])->name('loans.index');
    // Detail pinjaman & jadwal angsuran
    Route::get('/pinjaman/{loan}', [LoanController::class, 'show'])->name('loans.show');
});
