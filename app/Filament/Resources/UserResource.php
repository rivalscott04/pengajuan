<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Region;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Filament\Schemas\Components\Utilities\Get;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-users';
    protected static UnitEnum|string|null $navigationGroup = 'Konfigurasi';
    protected static ?string $navigationLabel = 'Pengguna';

    public static function getModelLabel(): string
    {
        return 'Pengguna';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Pengguna';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('admin');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->placeholder('Mis. Budi Operator')
                    ->required(),
                TextInput::make('email')
                    ->label('Email')
                    ->placeholder('nama@contoh.com')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->required(),
                TextInput::make('password')
                    ->label('Kata Sandi')
                    ->password()
                    ->placeholder('Isi untuk set / ubah sandi')
                    ->required(fn($operation) => $operation === 'create')
                    ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn($state) => filled($state)),
                Select::make('role')
                    ->label('Peran')
                    ->options(Role::query()->pluck('name', 'name'))
                    ->required()
                    ->native(false)
                    ->placeholder('Pilih peran'),
                Select::make('region_id')
                    ->label('Wilayah')
                    ->options(Region::query()->pluck('city_name', 'id'))
                    ->native(false)
                    ->placeholder('Pilih wilayah (untuk operator)')
                    ->required(fn(Get $get) => in_array($get('role'), ['operator_kabkota', 'operator_kanwil'])),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('roles.name')->label('Peran')->badge(),
                TextColumn::make('region.city_name')->label('Wilayah')->toggleable(),
            ])
            ->filters([])
            ->actions([
                Action::make('impersonate')
                    ->label('Impersonate')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('warning')
                    ->visible(fn (User $record) => auth()->user()?->canImpersonate() && $record->canBeImpersonated())
                    ->url(fn (User $record) => route('impersonate.start', $record), shouldOpenInNewTab: false),
                Action::make('edit')
                    ->label('Ubah')
                    ->icon('heroicon-o-pencil-square')
                    ->color('secondary')
                    ->url(fn(User $record) => static::getUrl('edit', ['record' => $record])),
                Action::make('delete')
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn(User $record) => $record->delete()),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

