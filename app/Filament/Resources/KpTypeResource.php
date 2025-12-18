<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KpTypeResource\Pages;
use App\Models\KpType;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;
use Illuminate\Database\Eloquent\Builder;

class KpTypeResource extends Resource
{
    protected static ?string $model = KpType::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static UnitEnum|string|null $navigationGroup = 'Konfigurasi';
    protected static ?string $navigationLabel = 'Jenis KP';

    public static function getModelLabel(): string
    {
        return 'Jenis KP';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Jenis KP';
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
                    ->placeholder('Mis. Kenaikan Pangkat Reguler')
                    ->required(),
                TextInput::make('code')
                    ->label('Kode')
                    ->placeholder('Mis. reguler')
                    ->required()
                    ->unique(ignoreRecord: true),
                Repeater::make('document_requirements')
                    ->label('Dokumen')
                    ->schema([
                        TextInput::make('label')
                            ->label('Nama Dokumen')
                            ->placeholder('Mis. SK Pangkat Terakhir')
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
                    ->formatStateUsing(fn($state) => is_array($state) ? count($state) : 0),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([]); // Filament 4: use table bulk actions API if needed later
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKpTypes::route('/'),
            'create' => Pages\CreateKpType::route('/create'),
            'edit' => Pages\EditKpType::route('/{record}/edit'),
        ];
    }

    /**
     * Hanya tampilkan jenis KP (bukan jenis gelar).
     * Konvensi: kode untuk pengajuan gelar diawali dengan "gelar_".
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('code', 'not like', 'gelar_%');
    }
}

