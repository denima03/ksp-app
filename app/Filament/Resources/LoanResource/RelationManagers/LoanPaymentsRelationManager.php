<?php

namespace App\Filament\Resources\LoanResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LoanPaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'loanPayments';

    protected static ?string $title = 'Riwayat Transaksi Pembayaran';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_pembayaran')
                    ->label('No. Bayar')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('loanSchedule.angsuran_ke')
                    ->label('Angsuran Ke-')
                    ->formatStateUsing(fn($state) => 'Ke-' . $state),

                TextColumn::make('jumlah_bayar')
                    ->label('Jumlah Bayar')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),

                TextColumn::make('metode_pembayaran')
                    ->label('Metode')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'gaji' => 'info',
                        'tpp' => 'warning',
                        'sertifikasi' => 'success',
                        'transfer' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'gaji' => 'Gaji',
                        'tpp' => 'TPP',
                        'sertifikasi' => 'Sertifikasi',
                        'transfer' => 'Transfer',
                        default => $state,
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'menunggu_konfirmasi' => 'warning',
                        'diverifikasi' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray',
                    }),

                ImageColumn::make('bukti_pembayaran')
                    ->label('Bukti')
                    ->circular(),

                TextColumn::make('tanggal_bayar')
                    ->label('Tgl Bayar')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
