<?php

namespace App\Filament\Resources\MemberResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TemporaryLoansRelationManager extends RelationManager
{
    protected static string $relationship = 'temporaryLoans';

    protected static ?string $title = 'Pinjaman Sementara';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_pinjaman')
                    ->label('No. Pinjaman')
                    ->searchable(),

                TextColumn::make('tanggal_pinjam')
                    ->label('Tgl Pinjam')
                    ->date('d M Y'),

                TextColumn::make('jumlah_pinjaman')
                    ->label('Jumlah')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.')),

                TextColumn::make('jumlah_bunga')
                    ->label('Bunga (2%)')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.')),

                TextColumn::make('total_pelunasan')
                    ->label('Total Pelunasan')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.')),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'aktif' => 'danger',
                        'lunas' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
