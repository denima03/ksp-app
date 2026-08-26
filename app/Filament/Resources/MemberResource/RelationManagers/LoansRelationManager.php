<?php

namespace App\Filament\Resources\MemberResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LoansRelationManager extends RelationManager
{
    protected static string $relationship = 'loans';

    protected static ?string $title = 'Pinjaman Reguler';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_pinjaman')
                    ->label('No. Pinjaman')
                    ->searchable(),

                TextColumn::make('tanggal_pinjaman')
                    ->label('Tgl Pinjam')
                    ->date('d M Y'),

                TextColumn::make('jumlah_pinjaman')
                    ->label('Jumlah Pinjaman')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.')),

                TextColumn::make('tenor')
                    ->label('Tenor')
                    ->formatStateUsing(fn($state) => $state . ' Bulan'),

                TextColumn::make('payment_source')
                    ->label('Sumber Bayar')
                    ->badge(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'aktif' => 'warning',
                        'lunas' => 'success',
                        'disetujui' => 'info',
                        'ditolak' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
