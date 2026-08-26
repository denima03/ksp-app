<?php

namespace App\Filament\Resources\MemberResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LoanPaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'loanPayments';

    protected static ?string $title = 'Riwayat Pembayaran';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal_bayar')
                    ->label('Tgl Bayar')
                    ->date('d M Y'),

                TextColumn::make('loan.nomor_pinjaman')
                    ->label('No. Pinjaman'),

                TextColumn::make('jumlah_bayar')
                    ->label('Jumlah Bayar')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.')),

                TextColumn::make('sumber_pembayaran')
                    ->label('Sumber')
                    ->badge(),

                TextColumn::make('nomor_referensi')
                    ->label('No. Ref'),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
