<?php

namespace App\Filament\Resources\TemporaryLoanResource\Pages;

use App\Filament\Resources\TemporaryLoanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTemporaryLoan extends CreateRecord
{
    protected static string $resource = TemporaryLoanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
