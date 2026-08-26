<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TemporaryLoanResource\Pages;
use App\Models\TemporaryLoan;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TemporaryLoanResource extends Resource
{
    protected static ?string $model = TemporaryLoan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Transaksi Koperasi';

    protected static ?string $modelLabel = 'Pinjaman Sementara';

    protected static ?string $pluralModelLabel = 'Pinjaman Sementara';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Anggota & Pinjaman')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('member_id')
                                ->relationship('member', 'nama')
                                ->label('Anggota')
                                ->searchable()
                                ->preload()
                                ->required(),

                            TextInput::make('nomor_pinjaman')
                                ->label('Nomor Pinjaman')
                                ->default(fn() => 'PJS-' . date('Ymd') . '-' . rand(100, 999))
                                ->required()
                                ->unique(ignoreRecord: true),

                            DatePicker::make('tanggal_pinjam')
                                ->label('Tanggal Pinjam')
                                ->default(now())
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set) {
                                    if ($state) {
                                        $set('tanggal_jatuh_tempo', \Carbon\Carbon::parse($state)->addMonth()->format('Y-m-d'));
                                    }
                                }),

                            DatePicker::make('tanggal_jatuh_tempo')
                                ->label('Tanggal Jatuh Tempo')
                                ->default(now()->addMonth())
                                ->required(),
                        ]),
                    ]),

                Section::make('Perhitungan Pinjaman & Bunga Flat 2%')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('jumlah_pinjaman')
                                ->label('Jumlah Pinjaman (Pokok)')
                                ->prefix('Rp')
                                ->numeric()
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $pokok = (float) $state;
                                    $bungaPersen = (float) $get('persen_bunga') ?: 2.0;
                                    $nominalBunga = $pokok * ($bungaPersen / 100);

                                    $set('jumlah_bunga', $nominalBunga);
                                    $set('total_pelunasan', $pokok + $nominalBunga);
                                }),

                            TextInput::make('persen_bunga')
                                ->label('Bunga Flat (%)')
                                ->suffix('%')
                                ->numeric()
                                ->default(2.0)
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $pokok = (float) $get('jumlah_pinjaman');
                                    $bungaPersen = (float) $state;
                                    $nominalBunga = $pokok * ($bungaPersen / 100);

                                    $set('jumlah_bunga', $nominalBunga);
                                    $set('total_pelunasan', $pokok + $nominalBunga);
                                }),

                            TextInput::make('jumlah_bunga')
                                ->label('Nominal Bunga (Rp)')
                                ->prefix('Rp')
                                ->numeric()
                                ->readOnly()
                                ->default(0),

                            TextInput::make('total_pelunasan')
                                ->label('Total Harus Dibayar (Pokok + Bunga)')
                                ->prefix('Rp')
                                ->numeric()
                                ->readOnly()
                                ->default(0),

                            Select::make('status')
                                ->label('Status Pinjaman')
                                ->options([
                                    'aktif' => 'Aktif',
                                    'lunas' => 'Lunas',
                                ])
                                ->default('aktif')
                                ->required(),

                            Textarea::make('keterangan')
                                ->label('Keperluan / Catatan')
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
                TextColumn::make('nomor_pinjaman')
                    ->label('No. Pinjaman')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('member.nama')
                    ->label('Anggota')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('jumlah_pinjaman')
                    ->label('Pokok')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),

                TextColumn::make('jumlah_bunga')
                    ->label('Bunga (2%)')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.')),

                TextColumn::make('total_pelunasan')
                    ->label('Total Pelunasan')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),

                TextColumn::make('tanggal_pinjam')
                    ->label('Tgl Pinjam')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('tanggal_jatuh_tempo')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'aktif' => 'danger',
                        'lunas' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'aktif' => 'Aktif',
                        'lunas' => 'Lunas',
                    ]),
            ])
            ->actions([
                Action::make('markAsLunas')
                    ->label('Tandai Lunas')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(TemporaryLoan $record) => $record->status === 'aktif')
                    ->action(function (TemporaryLoan $record) {
                        $record->update(['status' => 'lunas']);

                        Notification::make()
                            ->title('Pinjaman Sementara Lunas')
                            ->body('Status pinjaman telah diperbarui menjadi lunas.')
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
            'index' => Pages\ListTemporaryLoans::route('/'),
            'create' => Pages\CreateTemporaryLoan::route('/create'),
            'edit' => Pages\EditTemporaryLoan::route('/{record}/edit'),
        ];
    }
}
