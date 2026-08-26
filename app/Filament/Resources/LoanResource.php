<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanResource\Pages;
use App\Filament\Resources\LoanResource\RelationManagers\LoanPaymentsRelationManager;
use App\Filament\Resources\LoanResource\RelationManagers\LoanSchedulesRelationManager;
use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\LoanType;
use App\Models\Member;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LoanResource extends Resource
{
    protected static ?string $model = Loan::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Transaksi Koperasi';

    protected static ?string $modelLabel = 'Pinjaman Reguler';

    protected static ?string $pluralModelLabel = 'Pinjaman Reguler';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Anggota & Pengajuan')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('member_id')
                                ->label('Pilih Anggota')
                                ->options(Member::pluck('nama', 'id'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set) {
                                    $set('is_top_up', false);
                                    $set('previous_loan_id', null);
                                    $set('sisa_pinjaman_lama', 0);
                                }),

                            Select::make('loan_type_id')
                                ->label('Jenis Pinjaman')
                                ->options(LoanType::pluck('nama_jenis', 'id'))
                                ->searchable()
                                ->preload()
                                ->required(),

                            TextInput::make('nomor_pinjaman')
                                ->label('Nomor Pinjaman')
                                ->default(fn() => 'PJ-' . date('Ymd') . '-' . rand(100, 999))
                                ->required()
                                ->unique(ignoreRecord: true),

                            DatePicker::make('tanggal_pinjaman')
                                ->label('Tanggal Pengajuan')
                                ->default(now())
                                ->required(),
                        ]),
                    ]),

                Section::make('Detail Keuangan Pinjaman')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('jumlah_pinjaman')
                                ->label('Total Nominal Pinjaman Baru (Rp)')
                                ->prefix('Rp')
                                ->numeric()
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                    $jumlahBaru = (float) ($state ?? 0);
                                    if ($get('is_top_up')) {
                                        $sisaLama = (float) ($get('sisa_pinjaman_lama') ?? 0);
                                        $pencairan = max(0, $jumlahBaru - $sisaLama);
                                        $set('pencairan_bersih', $pencairan);
                                    } else {
                                        $set('pencairan_bersih', $jumlahBaru);
                                    }
                                })
                                ->helperText('Nominal total pinjaman baru (misal: 9.000.000)'),

                            TextInput::make('tenor')
                                ->label('Tenor (Bulan)')
                                ->suffix('Bulan')
                                ->numeric()
                                ->default(12)
                                ->required(),

                            TextInput::make('bunga')
                                ->label('Suku Bunga (%)')
                                ->suffix('%')
                                ->numeric()
                                ->default(2.0)
                                ->required(),

                            Select::make('tipe_bunga')
                                ->label('Tipe Bunga')
                                ->options([
                                    'flat' => 'Flat / Tetap',
                                    'effective' => 'Efektif / Menurun',
                                ])
                                ->default('flat')
                                ->required(),

                            Select::make('payment_source')
                                ->label('Sumber Skema Pemotongan')
                                ->options([
                                    'gaji' => 'Potong Gaji Utama',
                                    'tpp' => 'Potong TPP',
                                    'sertifikasi' => 'Potong Tunjangan Sertifikasi',
                                    'transfer' => 'Transfer Bank',
                                ])
                                ->default('gaji')
                                ->required(),

                            Select::make('status')
                                ->label('Status Pinjaman')
                                ->options([
                                    'pengajuan' => 'Pengajuan',
                                    'disetujui' => 'Disetujui',
                                    'aktif' => 'Aktif',
                                    'lunas' => 'Lunas',
                                    'ditolak' => 'Ditolak',
                                ])
                                ->default('disetujui')
                                ->required(),
                        ]),
                    ]),

                Section::make('Skema Top-Up Pinjaman (Opsional)')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('is_top_up')
                                ->label('Apakah Ini Pinjaman Top-Up?')
                                ->inline(false)
                                ->live()
                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                    if (! $state) {
                                        $set('previous_loan_id', null);
                                        $set('sisa_pinjaman_lama', 0);
                                        $jumlahBaru = (float) ($get('jumlah_pinjaman') ?? 0);
                                        $set('pencairan_bersih', $jumlahBaru);
                                        return;
                                    }

                                    $memberId = $get('member_id');
                                    if ($memberId) {
                                        $loanLama = Loan::where('member_id', $memberId)
                                            ->whereIn('status', ['aktif', 'disetujui'])
                                            ->latest()
                                            ->first();

                                        if ($loanLama) {
                                            $sisaPokokLama = LoanSchedule::where('loan_id', $loanLama->id)
                                                ->where('status', '!=', 'lunas')
                                                ->get()
                                                ->sum(function ($schedule) {
                                                    $terbayar = $schedule->jumlah_terbayar;
                                                    $pokok = $schedule->pokok;
                                                    return $terbayar >= $pokok ? 0 : ($pokok - $terbayar);
                                                });

                                            $set('previous_loan_id', $loanLama->id);
                                            $set('sisa_pinjaman_lama', $sisaPokokLama);

                                            $jumlahBaru = (float) ($get('jumlah_pinjaman') ?? 0);
                                            if ($jumlahBaru > $sisaPokokLama) {
                                                $set('pencairan_bersih', $jumlahBaru - $sisaPokokLama);
                                            } else {
                                                $set('pencairan_bersih', 0);
                                            }
                                        } else {
                                            $set('is_top_up', false);
                                            Notification::make()
                                                ->title('Tidak Ada Pinjaman Aktif')
                                                ->body('Anggota ini tidak memiliki pinjaman aktif yang dapat di-Top-Up.')
                                                ->warning()
                                                ->send();
                                        }
                                    } else {
                                        $set('is_top_up', false);
                                        Notification::make()
                                            ->title('Pilih Anggota Terlebih Dahulu')
                                            ->body('Silakan pilih Anggota terlebih dahulu.')
                                            ->warning()
                                            ->send();
                                    }
                                }),

                            Hidden::make('previous_loan_id'),

                            TextInput::make('sisa_pinjaman_lama')
                                ->label('Potongan Sisa Pokok Pinjaman Lama')
                                ->prefix('Rp')
                                ->numeric()
                                ->readOnly()
                                ->helperText('Pelunasan otomatis sisa pokok pinjaman sebelumnya (misal: 5.000.000)'),

                            TextInput::make('pencairan_bersih')
                                ->label('Uang Diterima Anggota (Pencairan Bersih)')
                                ->prefix('Rp')
                                ->numeric()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                    if ($get('is_top_up')) {
                                        $pencairan = (float) ($state ?? 0);
                                        $sisaLama = (float) ($get('sisa_pinjaman_lama') ?? 0);
                                        // Rumus: Total Pinjaman Baru = Uang Diterima + Sisa Pokok Lama (4jt + 5jt = 9jt)
                                        $set('jumlah_pinjaman', $pencairan + $sisaLama);
                                    }
                                })
                                ->helperText('Isi uang yang ingin dibawa pulang anggota (misal: 4.000.000). Total pinjaman akan otomatis jadi 9.000.000.'),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_pinjaman')
                    ->label('No. Pinjaman')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('member.nama')
                    ->label('Nama Anggota')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('loanType.nama_jenis')
                    ->label('Jenis Pinjaman'),

                TextColumn::make('jumlah_pinjaman')
                    ->label('Nominal')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),

                TextColumn::make('tenor')
                    ->label('Tenor')
                    ->formatStateUsing(fn($state) => $state . ' Bln'),

                IconColumn::make('is_top_up')
                    ->label('Top-Up')
                    ->boolean(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pengajuan' => 'warning',
                        'disetujui' => 'info',
                        'aktif' => 'primary',
                        'lunas' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('tanggal_pinjaman')
                    ->label('Tgl Pinjaman')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pengajuan' => 'Pengajuan',
                        'disetujui' => 'Disetujui',
                        'aktif' => 'Aktif',
                        'lunas' => 'Lunas',
                        'ditolak' => 'Ditolak',
                    ]),
                Tables\Filters\TernaryFilter::make('is_top_up')
                    ->label('Status Top-Up'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            LoanSchedulesRelationManager::class,
            LoanPaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoans::route('/'),
            'create' => Pages\CreateLoan::route('/create'),
            'edit' => Pages\EditLoan::route('/{record}/edit'),
        ];
    }
}
