<?php

namespace App\Filament\Resources\TemporaryLoanResource\Pages;

use App\Filament\Resources\TemporaryLoanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTemporaryLoan extends EditRecord
{
    protected static string $resource = TemporaryLoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
