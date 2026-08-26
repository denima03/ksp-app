<?php

namespace App\Filament\Resources\LoanResource\RelationManagers;

use App\Models\LoanPayment;
use App\Models\LoanSchedule;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LoanSchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'loanSchedules';

    protected static ?string $title = 'Jadwal Angsuran Pinjaman';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('angsuran_ke')
            ->columns([
                TextColumn::make('angsuran_ke')
                    ->label('Angsuran Ke')
                    ->sortable(),

                TextColumn::make('tanggal_jatuh_tempo')
                    ->label('Tgl Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('pokok')
                    ->label('Pokok')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format((float) $state, 0, ',', '.')),

                TextColumn::make('bunga')
                    ->label('Bunga')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format((float) $state, 0, ',', '.')),

                TextColumn::make('jumlah_angsuran')
                    ->label('Total Angsuran')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format((float) $state, 0, ',', '.')),

                TextColumn::make('jumlah_terbayar')
                    ->label('Terbayar')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format((float) $state, 0, ',', '.')),

                TextColumn::make('sisa_pokok')
                    ->label('Sisa Pokok')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format((float) $state, 0, ',', '.')),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'lunas' => 'success',
                        'sebagian' => 'warning',
                        'belum_bayar' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('angsuran_ke', 'asc')
            ->filters([])
            ->headerActions([])
            ->actions([
                Action::make('bayar')
                    ->label('Bayar')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->button()
                    ->hidden(fn(LoanSchedule $record) => $record->status === 'lunas')
                    ->form([
                        TextInput::make('jumlah_bayar')
                            ->label('Nominal Pembayaran (Rp)')
                            ->prefix('Rp')
                            ->numeric()
                            ->default(fn(LoanSchedule $record) => max(0, $record->jumlah_angsuran - $record->jumlah_terbayar))
                            ->required(),

                        Select::make('metode_pembayaran')
                            ->label('Metode Pembayaran')
                            ->options([
                                'gaji' => 'Potong Gaji',
                                'tpp' => 'Potong TPP',
                                'sertifikasi' => 'Potong Tunjangan Sertifikasi',
                                'transfer' => 'Transfer Bank',
                                'tunai' => 'Tunai / Cash',
                            ])
                            ->default('gaji')
                            ->required(),

                        DatePicker::make('tanggal_bayar')
                            ->label('Tanggal Pembayaran')
                            ->default(now())
                            ->required(),

                        TextInput::make('catatan')
                            ->label('Catatan / Keterangan')
                            ->placeholder('Contoh: Pembayaran angsuran via kasir/bendahara'),
                    ])
                    ->action(function (LoanSchedule $record, array $data): void {
                        $jumlahBayar = (float) $data['jumlah_bayar'];

                        // 1. Simpan Riwayat Pembayaran ke tabel loan_payments
                        LoanPayment::create([
                            'loan_id' => $record->loan_id,
                            'loan_schedule_id' => $record->id,
                            'nomor_pembayaran' => 'PAY-' . date('Ymd') . '-' . rand(100, 999),
                            'tanggal_bayar' => $data['tanggal_bayar'],
                            'jumlah_bayar' => $jumlahBayar,
                            'metode_pembayaran' => $data['metode_pembayaran'],
                            'status' => 'diverifikasi',
                            'catatan' => $data['catatan'] ?? null,
                        ]);

                        // 2. Hitung Sisa dan Update Status Jadwal
                        $totalTerbayarBaru = (float) $record->jumlah_terbayar + $jumlahBayar;
                        $statusBaru = $totalTerbayarBaru >= (float) $record->jumlah_angsuran ? 'lunas' : 'sebagian';

                        $pokokTerbayar = min((float) $record->pokok, $totalTerbayarBaru);
                        $bungaTerbayar = max(0, $totalTerbayarBaru - $pokokTerbayar);

                        $record->update([
                            'pokok_terbayar' => $pokokTerbayar,
                            'bunga_terbayar' => $bungaTerbayar,
                            'jumlah_terbayar' => $totalTerbayarBaru,
                            'status' => $statusBaru,
                            'tanggal_bayar' => $data['tanggal_bayar'],
                        ]);

                        // 3. Otomatis LUNAS-kan Pinjaman Utama jika seluruh angsuran telah lunas
                        $sisaBelumLunas = LoanSchedule::where('loan_id', $record->loan_id)
                            ->where('status', '!=', 'lunas')
                            ->count();

                        if ($sisaBelumLunas === 0) {
                            $record->loan->update(['status' => 'lunas']);
                        }

                        Notification::make()
                            ->title('Pembayaran Berhasil')
                            ->body("Angsuran Ke-{$record->angsuran_ke} berhasil dibayar sebesar Rp " . number_format($jumlahBayar, 0, ',', '.'))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }
}
