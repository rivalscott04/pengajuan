<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegionResource\Pages;
use App\Models\Region;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use BackedEnum;
use UnitEnum;

class RegionResource extends Resource
{
    protected static ?string $model = Region::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-map-pin';
    protected static UnitEnum|string|null $navigationGroup = 'Konfigurasi';
    protected static ?string $navigationLabel = 'Wilayah';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user?->hasRole('admin');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('city_name')->label('Kota/Kab'),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false; // tidak perlu tampil di menu
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegions::route('/'),
        ];
    }
}

