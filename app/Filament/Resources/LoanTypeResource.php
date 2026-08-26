<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanTypeResource\Pages;
use App\Models\LoanType;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LoanTypeResource extends Resource
{
    protected static ?string $model = LoanType::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $modelLabel = 'Jenis Pinjaman';

    protected static ?string $pluralModelLabel = 'Jenis Pinjaman';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Konfigurasi Jenis Pinjaman')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('kode_jenis')
                                ->label('Kode Jenis')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->placeholder('Contoh: REG / TEMP / KHS'),

                            TextInput::make('nama_jenis')
                                ->label('Nama Jenis Pinjaman')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Contoh: Pinjaman Reguler 10 Bulan'),

                            TextInput::make('bunga_per_tahun')
                                ->label('Persentase Bunga Per Bulan (%)')
                                ->numeric()
                                ->suffix('%')
                                ->default(2.0)
                                ->required(),

                            TextInput::make('maksimal_tenor')
                                ->label('Maksimal Tenor (Bulan)')
                                ->numeric()
                                ->suffix('Bulan')
                                ->default(10)
                                ->required(),

                            TextInput::make('maksimal_pinjaman')
                                ->label('Maksimal Plafon Pinjaman')
                                ->prefix('Rp')
                                ->numeric()
                                ->default(10000000)
                                ->required(),

                            Toggle::make('is_active')
                                ->label('Status Aktif')
                                ->default(true)
                                ->inline(false),

                            Textarea::make('deskripsi')
                                ->label('Deskripsi / Ketentuan')
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
                TextColumn::make('kode_jenis')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nama_jenis')
                    ->label('Nama Jenis Pinjaman')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('bunga_per_tahun')
                    ->label('Bunga / Bulan')
                    ->formatStateUsing(fn($state) => $state . ' %')
                    ->sortable(),

                TextColumn::make('maksimal_tenor')
                    ->label('Max Tenor')
                    ->formatStateUsing(fn($state) => $state . ' Bulan'),

                TextColumn::make('maksimal_pinjaman')
                    ->label('Max Plafon')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListLoanTypes::route('/'),
            'create' => Pages\CreateLoanType::route('/create'),
            'edit' => Pages\EditLoanType::route('/{record}/edit'),
        ];
    }
}
