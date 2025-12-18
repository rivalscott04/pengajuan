<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnnouncementResource\Pages;
use App\Models\Announcement;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-megaphone';
    protected static UnitEnum|string|null $navigationGroup = 'Konfigurasi';
    protected static ?string $navigationLabel = 'Pengumuman';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('admin');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Judul full width
                TextInput::make('title')
                    ->label('Judul Pengumuman')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Contoh: Batas Pengusulan KP Periode Januari 2025')
                    ->helperText('Judul pengumuman yang akan ditampilkan di dashboard')
                    ->columnSpanFull(),
                
                // Isi pengumuman full width
                RichEditor::make('content')
                    ->label('Isi Pengumuman')
                    ->required()
                    ->placeholder('Contoh: Batas pengusulan KP periode ini sampai tanggal 31 Januari 2025 pukul 23:59 WITA')
                    ->helperText('Isi pengumuman. Bisa menggunakan format teks atau HTML sederhana.')
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'bulletList',
                        'orderedList',
                    ])
                    ->columnSpanFull(),
                
                // Baris 1: Jenis Banner dan Status Aktif
                Select::make('type')
                    ->label('Jenis Banner')
                    ->options([
                        'info' => 'Info (Biru)',
                        'warning' => 'Peringatan (Kuning)',
                        'success' => 'Sukses (Hijau)',
                        'danger' => 'Bahaya (Merah)',
                    ])
                    ->default('info')
                    ->required()
                    ->helperText('Warna banner pengumuman')
                    ->columnSpan(1),
                
                Toggle::make('is_active')
                    ->label('Status Aktif')
                    ->default(true)
                    ->helperText('Nonaktifkan untuk menyembunyikan pengumuman')
                    ->columnSpan(1),
                
                // Baris 2: Prioritas dan Tanggal
                TextInput::make('priority')
                    ->label('Prioritas')
                    ->numeric()
                    ->default(0)
                    ->helperText('Angka lebih kecil = tampil lebih atas. Default: 0')
                    ->columnSpan(1),
                
                DateTimePicker::make('ends_at')
                    ->label('Batas Tanggal')
                    ->helperText('Tanggal batas pengusulan atau kapan pengumuman berakhir')
                    ->displayFormat('d/m/Y H:i')
                    ->timezone('Asia/Makassar')
                    ->required()
                    ->columnSpan(1),
                
                // Mulai tampil (opsional, full width jika perlu)
                DateTimePicker::make('starts_at')
                    ->label('Mulai Tampil (Opsional)')
                    ->helperText('Kosongkan jika pengumuman langsung aktif. Isi jika ingin jadwalkan tampil di waktu tertentu')
                    ->displayFormat('d/m/Y H:i')
                    ->timezone('Asia/Makassar')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                
                BadgeColumn::make('type')
                    ->label('Jenis')
                    ->colors([
                        'info' => 'info',
                        'warning' => 'warning',
                        'success' => 'success',
                        'danger' => 'danger',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'info' => 'Info',
                        'warning' => 'Peringatan',
                        'success' => 'Sukses',
                        'danger' => 'Bahaya',
                        default => $state,
                    }),
                
                BadgeColumn::make('is_active')
                    ->label('Status')
                    ->colors([
                        'success' => true,
                        'gray' => false,
                    ])
                    ->formatStateUsing(fn ($state) => $state ? 'Aktif' : 'Nonaktif'),
                
                TextColumn::make('ends_at')
                    ->label('Batas Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                
                TextColumn::make('priority')
                    ->label('Prioritas')
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis')
                    ->options([
                        'info' => 'Info',
                        'warning' => 'Peringatan',
                        'success' => 'Sukses',
                        'danger' => 'Bahaya',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('priority')
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'edit' => Pages\EditAnnouncement::route('/{record}/edit'),
        ];
    }
}

