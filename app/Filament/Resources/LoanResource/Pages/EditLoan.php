<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
use App\Services\PaymentScheduleService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLoan extends EditRecord
{
    protected static string $resource = LoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $loan = $this->record;

        // Kapan pun data pinjaman diedit (misal nominal, tenor, atau status disetujui/aktif),
        // jadwal angsuran langsung di-regenerate agar selalu cocok.
        $statusStr = is_object($loan->status) ? $loan->status->value : (string) $loan->status;
        if (in_array($statusStr, ['disetujui', 'aktif', 'pengajuan'])) {
            $scheduleService = app(PaymentScheduleService::class);
            $scheduleService->createSchedulesForLoan($loan);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
