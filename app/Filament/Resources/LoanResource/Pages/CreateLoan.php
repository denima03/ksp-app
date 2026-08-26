<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanSchedule;
use App\Services\PaymentScheduleService;
use Filament\Resources\Pages\CreateRecord;

class CreateLoan extends CreateRecord
{
    protected static string $resource = LoanResource::class;

    protected function afterCreate(): void
    {
        $newLoan = $this->record;

        // 1. Eksekusi Pelunasan Pinjaman Lama jika Top-Up
        if ($newLoan->is_top_up && $newLoan->previous_loan_id) {
            $oldLoan = Loan::find($newLoan->previous_loan_id);

            if ($oldLoan) {
                $unpaidSchedules = LoanSchedule::where('loan_id', $oldLoan->id)
                    ->where('status', '!=', 'lunas')
                    ->get();

                foreach ($unpaidSchedules as $schedule) {
                    $terbayar = (float) $schedule->pokok_terbayar;
                    $pokok = (float) $schedule->pokok;
                    $sisaPokok = $terbayar >= $pokok ? 0 : ($pokok - $terbayar);

                    if ($sisaPokok > 0) {
                        LoanPayment::create([
                            'loan_id' => $oldLoan->id,
                            'loan_schedule_id' => $schedule->id,
                            'nomor_pembayaran' => 'TOPUP-' . date('Ymd') . '-' . rand(100, 999),
                            'tanggal_bayar' => now(),
                            'jumlah_bayar' => $sisaPokok,
                            'metode_pembayaran' => 'potong_topup',
                            'status' => 'diverifikasi',
                            'catatan' => "Pelunasan sisa pokok via Top-Up Pinjaman Baru #{$newLoan->nomor_pinjaman}",
                        ]);
                    }

                    // Update jadwal lama menjadi LUNAS sempurna
                    $schedule->update([
                        'pokok_terbayar' => $pokok,
                        'bunga_terbayar' => 0,
                        'jumlah_terbayar' => $pokok,
                        'sisa_pokok' => 0,
                        'status' => 'lunas',
                        'tanggal_bayar' => now(),
                    ]);
                }

                $oldLoan->update(['status' => 'lunas']);
            }
        }

        // 2. Generate Penuh Jadwal Pinjaman Baru (1 s/d Tenor Baru)
        $scheduleService = app(PaymentScheduleService::class);
        $scheduleService->createSchedulesForLoan($newLoan);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
