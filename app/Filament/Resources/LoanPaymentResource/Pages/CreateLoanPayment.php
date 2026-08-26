<?php

namespace App\Filament\Resources\LoanPaymentResource\Pages;

use App\Enums\LoanStatus;
use App\Filament\Resources\LoanPaymentResource;
use App\Models\LoanSchedule;
use Filament\Resources\Pages\CreateRecord;

class CreateLoanPayment extends CreateRecord
{
    protected static string $resource = LoanPaymentResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;

        // Jika status langsung 'diverifikasi', otomatis perbarui jadwal angsurannya
        if ($record->status === 'diverifikasi' && $record->loan_schedule_id) {
            $schedule = $record->loanSchedule;
            if ($schedule) {
                $totalTerbayar = $schedule->jumlah_terbayar + $record->jumlah_bayar;
                $statusJadwal = $totalTerbayar >= $schedule->jumlah_angsuran ? 'lunas' : 'sebagian';

                $schedule->update([
                    'jumlah_terbayar' => $totalTerbayar,
                    'status' => $statusJadwal,
                ]);
            }

            // Cek jika seluruh angsuran lunas
            if ($record->loan_id) {
                $loan = $record->loan;
                $sisaBelumLunas = LoanSchedule::where('loan_id', $loan->id)
                    ->where('status', '!=', 'lunas')
                    ->count();

                if ($sisaBelumLunas === 0) {
                    $loan->update(['status' => LoanStatus::LUNAS->value]);
                }
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
