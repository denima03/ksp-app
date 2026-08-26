<?php

namespace App\Filament\Resources\TemporaryLoanResource\Pages;

use App\Filament\Resources\TemporaryLoanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTemporaryLoans extends ListRecords
{
    protected static string $resource = TemporaryLoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
