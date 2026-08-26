<?php

namespace App\Filament\Resources;

use App\Enums\LoanStatus;
use App\Filament\Resources\LoanPaymentResource\Pages;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanSchedule;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LoanPaymentResource extends Resource
{
    protected static ?string $model = LoanPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Transaksi Koperasi';

    protected static ?string $modelLabel = 'Pembayaran Angsuran';

    protected static ?string $pluralModelLabel = 'Pembayaran Angsuran';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Transaksi Angsuran')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('loan_id')
                                ->label('Pilih Pinjaman')
                                ->options(
                                    Loan::with('member')
                                        ->whereIn('status', [LoanStatus::AKTIF->value, LoanStatus::DISETUJUI->value])
                                        ->get()
                                        ->pluck('nomor_pinjaman_dengan_nama', 'id')
                                )
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn(Set $set) => $set('loan_schedule_id', null)),

                            Select::make('loan_schedule_id')
                                ->label('Angsuran Bulan Ke-')
                                ->options(function (Get $get) {
                                    $loanId = $get('loan_id');
                                    if (! $loanId) return [];

                                    return LoanSchedule::where('loan_id', $loanId)
                                        ->where('status', '!=', 'lunas')
                                        ->get()
                                        ->mapWithKeys(fn($schedule) => [
                                            $schedule->id => "Angsuran ke-{$schedule->angsuran_ke} (Rp " . number_format($schedule->jumlah_angsuran, 0, ',', '.') . ")"
                                        ]);
                                })
                                ->searchable()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set) {
                                    if ($state) {
                                        $schedule = LoanSchedule::find($state);
                                        if ($schedule) {
                                            $sisa = $schedule->jumlah_angsuran - $schedule->jumlah_terbayar;
                                            $set('jumlah_bayar', $sisa > 0 ? $sisa : $schedule->jumlah_angsuran);
                                        }
                                    }
                                }),

                            TextInput::make('nomor_pembayaran')
                                ->label('Nomor Transaksi')
                                ->default(fn() => 'PAY-' . date('Ymd') . '-' . rand(100, 999))
                                ->required()
                                ->unique(ignoreRecord: true),

                            DatePicker::make('tanggal_bayar')
                                ->label('Tanggal Bayar')
                                ->default(now())
                                ->required(),

                            TextInput::make('jumlah_bayar')
                                ->label('Jumlah Pembayaran (Rp)')
                                ->prefix('Rp')
                                ->numeric()
                                ->required(),

                            Select::make('metode_pembayaran')
                                ->label('Metode Pembayaran')
                                ->options([
                                    'gaji' => 'Potong Gaji Utama',
                                    'tpp' => 'Potong TPP',
                                    'sertifikasi' => 'Potong Tunjangan Sertifikasi',
                                    'transfer' => 'Transfer Bank',
                                ])
                                ->default('gaji')
                                ->required(),

                            Select::make('status')
                                ->label('Status Verifikasi')
                                ->options([
                                    'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
                                    'diverifikasi' => 'Diverifikasi',
                                    'ditolak' => 'Ditolak',
                                ])
                                ->default('diverifikasi')
                                ->required(),

                            FileUpload::make('bukti_pembayaran')
                                ->label('Upload Bukti Transfer / Slip Potongan')
                                ->image()
                                ->directory('bukti-pembayaran')
                                ->columnSpanFull()
                                ->nullable(),

                            Textarea::make('catatan')
                                ->label('Catatan Keterangan')
                                ->columnSpanFull()
                                ->nullable(),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_pembayaran')
                    ->label('No. Bayar')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('loan.member.nama')
                    ->label('Nama Anggota')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('loan.nomor_pinjaman')
                    ->label('No. Pinjaman')
                    ->searchable(),

                TextColumn::make('loanSchedule.angsuran_ke')
                    ->label('Ke-')
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
            ->filters([
                Tables\Filters\SelectFilter::make('metode_pembayaran')
                    ->label('Metode Pembayaran')
                    ->options([
                        'gaji' => 'Potong Gaji Utama',
                        'tpp' => 'Potong TPP',
                        'sertifikasi' => 'Potong Tunjangan Sertifikasi',
                        'transfer' => 'Transfer Bank',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
                        'diverifikasi' => 'Diverifikasi',
                        'ditolak' => 'Ditolak',
                    ]),
            ])
            ->actions([
                Action::make('verify')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(LoanPayment $record) => $record->status === 'menunggu_konfirmasi')
                    ->action(function (LoanPayment $record) {
                        $record->update(['status' => 'diverifikasi']);

                        if ($record->loan_schedule_id) {
                            $schedule = $record->loanSchedule;
                            if ($schedule) {
                                $totalTerbayar = $schedule->jumlah_terbayar + $record->jumlah_bayar;
                                $statusJadwal = $totalTerbayar >= $schedule->jumlah_angsuran ? 'lunas' : 'sebagian';

                                $schedule->update([
                                    'jumlah_terbayar' => $totalTerbayar,
                                    'status' => $statusJadwal,
                                ]);
                            }
                        }

                        if ($record->loan_id) {
                            $loan = $record->loan;
                            $sisaJadwalBelumLunas = LoanSchedule::where('loan_id', $loan->id)
                                ->where('status', '!=', 'lunas')
                                ->count();

                            if ($sisaJadwalBelumLunas === 0) {
                                $loan->update(['status' => LoanStatus::LUNAS->value]);
                            }
                        }

                        Notification::make()
                            ->title('Pembayaran Diverifikasi')
                            ->body('Status angsuran dan pinjaman berhasil diperbarui.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoanPayments::route('/'),
            'create' => Pages\CreateLoanPayment::route('/create'),
            'edit' => Pages\EditLoanPayment::route('/{record}/edit'),
        ];
    }
}
