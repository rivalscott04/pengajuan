<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use BackedEnum;
use UnitEnum;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static UnitEnum|string|null $navigationGroup = 'Sistem';
    protected static ?string $navigationLabel = 'Audit Log';

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole('admin');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('submission.id')
                    ->label('ID Pengajuan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('submission.applicant_name')
                    ->label('Nama Pemohon')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(),
                TextColumn::make('action')
                    ->label('Aksi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'submit', 'approve' => 'success',
                        'reject', 'delete' => 'danger',
                        'return' => 'warning',
                        'download_zip', 'download_bulk_zip' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'submit' => 'Ajukan',
                        'approve' => 'Setujui',
                        'reject' => 'Tolak',
                        'return' => 'Kembalikan',
                        'delete' => 'Hapus',
                        'download_zip' => 'Download ZIP',
                        'download_bulk_zip' => 'Download ZIP Bulk',
                        'change_status' => 'Ubah Status',
                        default => $state,
                    }),
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Aksi')
                    ->options([
                        'submit' => 'Ajukan',
                        'approve' => 'Setujui',
                        'reject' => 'Tolak',
                        'return' => 'Kembalikan',
                        'delete' => 'Hapus',
                        'download_zip' => 'Download ZIP',
                        'download_bulk_zip' => 'Download ZIP Bulk',
                        'change_status' => 'Ubah Status',
                    ])
                    ->multiple(),
                Filter::make('created_at')
                    ->label('Periode')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Dari Tanggal')
                            ->displayFormat('d/m/Y')
                            ->timezone('Asia/Makassar'),
                        DatePicker::make('created_until')
                            ->label('Sampai Tanggal')
                            ->displayFormat('d/m/Y')
                            ->timezone('Asia/Makassar'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }
}

