<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GelarTypeResource\Pages;
use App\Models\KpType;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class GelarTypeResource extends Resource
{
    protected static ?string $model = KpType::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-academic-cap';
    protected static UnitEnum|string|null $navigationGroup = 'Konfigurasi';
    protected static ?string $navigationLabel = 'Jenis Gelar';

    public static function getModelLabel(): string
    {
        return 'Jenis Gelar';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Jenis Gelar';
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->hasRole('admin');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->placeholder('Mis. Penyematan Gelar S1')
                    ->required(),
                TextInput::make('code')
                    ->label('Kode')
                    ->placeholder('Mis. gelar_s1')
                    ->helperText('Sebaiknya diawali dengan "gelar_", mis. gelar_s1, gelar_s2')
                    ->required()
                    ->unique(ignoreRecord: true),
                Repeater::make('document_requirements')
                    ->label('Dokumen')
                    ->schema([
                        TextInput::make('label')
                            ->label('Nama Dokumen')
                            ->placeholder('Mis. Ijazah S1')
                            ->required(),
                        TagsInput::make('allowed_types')
                            ->label('Tipe File (mis: pdf, jpg)')
                            ->placeholder('Contoh: pdf, jpg, png')
                            ->suggestions(['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'])
                            ->separator(',')
                            ->splitKeys([',', ' '])
                            ->required(),
                        TextInput::make('size_number')
                            ->label('Ukuran Maks')
                            ->placeholder('Mis. 5')
                            ->numeric()
                            ->minValue(1)
                            ->default(5)
                            ->required(),
                        Select::make('size_unit')
                            ->label('Satuan')
                            ->options([
                                'KB' => 'KB',
                                'MB' => 'MB',
                            ])
                            ->default('MB')
                            ->required(),
                    ])
                    ->columns(1)
                    ->createItemButtonLabel('Tambah Dokumen'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('code')->label('Kode')->searchable(),
                TextColumn::make('document_requirements')
                    ->label('Jumlah Dokumen')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) : 0),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGelarTypes::route('/'),
            'create' => Pages\CreateGelarType::route('/create'),
            'edit' => Pages\EditGelarType::route('/{record}/edit'),
        ];
    }

    /**
     * Hanya tampilkan jenis yang dipakai untuk penyematan gelar.
     * Konvensi: kode diawali dengan "gelar_".
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('code', 'like', 'gelar_%');
    }
}


