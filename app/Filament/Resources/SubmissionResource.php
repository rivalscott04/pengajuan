<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubmissionResource\Pages;
use App\Models\KpType;
use App\Models\Region;
use App\Models\Submission;
use App\Models\SubmissionDocument;
use App\Services\AuditLogger;
use App\Services\EmployeeService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use ZipArchive;
use BackedEnum;
use UnitEnum;

class SubmissionResource extends Resource
{
    protected static ?string $model = Submission::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static UnitEnum|string|null $navigationGroup = 'Pengajuan KP';
    protected static ?string $navigationLabel = 'Pengajuan KP';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user?->hasAnyRole(['admin', 'operator_kabkota', 'operator_kanwil']);
    }

    public static function form(Schema $schema): Schema
    {
        $baseComponents = [
            Hidden::make('status')->default(Submission::STATUS_DRAFT),
            Select::make('kp_type_id')
                ->label('Jenis KP')
                ->options(
                    KpType::query()
                        ->where('code', 'not like', 'gelar_%')
                        ->pluck('name', 'id')
                )
                ->required()
                ->live()
                ->placeholder('Pilih jenis KP')
                ->helperText('Pilih jenis kenaikan pangkat yang akan diajukan'),
            Hidden::make('region_id')
                ->default(fn() => Auth::user()?->region_id),
            Select::make('nip')
                ->label('NIP / Nama Pegawai')
                ->searchable()
                ->getSearchResultsUsing(function (?string $search): array {
                    if (! filled($search) || mb_strlen(trim($search)) < 2) {
                        return [];
                    }

                    /** @var EmployeeService $service */
                    $service = app(EmployeeService::class);
                    $results = $service->search(trim($search), 50);

                    $mapped = [];
                    foreach ($results as $employee) {
                        $nip = trim((string) ($employee['nip'] ?? ''));
                        $name = trim((string) ($employee['applicant_name'] ?? ''));
                        
                        if (empty($nip) || empty($name)) {
                            continue;
                        }
                        
                        $mapped[$nip] = $nip . ' - ' . $name;
                    }

                    return $mapped;
                })
                ->getOptionLabelUsing(function ($value): ?string {
                    if (! $value) {
                        return null;
                    }
                    
                    /** @var EmployeeService $service */
                    $service = app(EmployeeService::class);
                    $employee = $service->findByNip($value);
                    
                    if (! $employee) {
                        return null;
                    }
                    
                    return $employee['nip'] . ' - ' . $employee['applicant_name'];
                })
                ->helperText('Cari NIP atau nama pegawai, lalu pilih. Data lain akan terisi otomatis.')
                ->placeholder('Ketik NIP atau nama pegawai')
                ->native(false)
                ->required()
                ->live()
                ->afterStateUpdated(function (?string $state, Set $set) {
                    if (! filled($state)) {
                        return;
                    }

                    /** @var EmployeeService $service */
                    $service = app(EmployeeService::class);
                    $employee = $service->findByNip($state);

                    if (! $employee) {
                        Notification::make()->title('Pegawai tidak ditemukan')->danger()->send();
                        return;
                    }

                    // Set semua field dari data pegawai
                    $set('employee_id', $employee['employee_id'] ?? null);
                    $set('employee_external_id', $employee['employee_external_id']);
                    $set('applicant_name', $employee['applicant_name']);
                    $set('satuan_kerja', $employee['satuan_kerja']);
                    $set('jabatan', $employee['jabatan']);
                    $set('pangkat_sekarang', $employee['pangkat_sekarang']);
                    $set('tmt_pangkat', $employee['tmt_pangkat']);
                    $set('masa_kerja', $employee['masa_kerja']);
                    $set('pendidikan', $employee['pendidikan']);
                    $set('tanggal_lahir', $employee['tanggal_lahir']);

                    Notification::make()->title('Data pegawai diambil')->success()->send();
                }),
            Hidden::make('employee_id'),
            TextInput::make('employee_external_id')
                ->label('NIP (Snapshot)')
                ->disabled()
                ->dehydrated(),
            TextInput::make('applicant_name')
                ->label('Nama')
                ->disabled()
                ->dehydrated(),
            TextInput::make('satuan_kerja')
                ->label('Satuan Kerja')
                ->disabled()
                ->dehydrated(),
            TextInput::make('jabatan')
                ->label('Jabatan')
                ->disabled()
                ->dehydrated(),
            TextInput::make('pangkat_sekarang')
                ->label('Pangkat Sekarang')
                ->disabled()
                ->dehydrated(),
            TextInput::make('tmt_pangkat')
                ->label('TMT Pangkat')
                ->disabled()
                ->dehydrated(),
            TextInput::make('masa_kerja')
                ->label('Masa Kerja')
                ->disabled()
                ->dehydrated(),
            TextInput::make('pendidikan')
                ->label('Pendidikan')
                ->disabled()
                ->dehydrated(),
            TextInput::make('tanggal_lahir')
                ->label('Tanggal Lahir')
                ->disabled()
                ->dehydrated(),
        ];

        // Add document fields dynamically - get all KP types and create fields for each
        // Each field will be visible only when the matching kp_type_id is selected
        $allKpTypes = KpType::all();
        $documentFieldsMap = [];
        
        foreach ($allKpTypes as $kpType) {
            $fields = static::documentFields($kpType->id);
            foreach ($fields as $field) {
                $fieldName = $field->getName();
                // Use field name as key to avoid duplicates
                if (!isset($documentFieldsMap[$fieldName])) {
                    $docKey = str_replace('documents.', '', $fieldName);
                    $documentFieldsMap[$fieldName] = $field->visible(function (Get $get) use ($docKey, $allKpTypes) {
                        $kpTypeId = $get('kp_type_id');
                        if (!$kpTypeId) {
                            return false;
                        }
                        // Gunakan collection yang sudah di-load untuk menghindari query tambahan
                        $selectedKpType = $allKpTypes->firstWhere('id', $kpTypeId);
                        if (!$selectedKpType) {
                            return false;
                        }
                        $requirements = $selectedKpType->document_requirements ?? [];
                        return collect($requirements)->contains('key', $docKey);
                    });
                }
            }
        }
        
        $components = array_merge($baseComponents, array_values($documentFieldsMap));

        // Add verifikator notes field
        $components[] = Textarea::make('verifikator_notes')
            ->label('Catatan Verifikator')
            ->placeholder('Catatan dari verifikator (jika ada)')
            ->disabled(fn ($record) => $record?->status !== Submission::STATUS_DIKEMBALIKAN)
            ->visible(fn ($record) => $record && filled($record->verifikator_notes));

        return $schema
            ->components($components)
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('applicant_name')->label('Nama')->searchable(),
                TextColumn::make('nip')->label('NIP')->searchable(),
                TextColumn::make('kpType.name')->label('Jenis KP'),
                TextColumn::make('region.city_name')
                    ->label('Wilayah')
                    ->visible(fn () => Auth::user()?->hasAnyRole(['admin', 'operator_kanwil'])),
                BadgeColumn::make('status')
                    ->colors([
                        'gray' => Submission::STATUS_DRAFT,
                        'warning' => Submission::STATUS_DIAJUKAN,
                        'danger' => Submission::STATUS_DIKEMBALIKAN,
                        'success' => Submission::STATUS_DISETUJUI,
                        'secondary' => Submission::STATUS_DITOLAK,
                    ]),
                TextColumn::make('submitted_at')
                    ->label('Terkirim')
                    ->dateTime('d M Y H:i'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        Submission::STATUS_DRAFT => 'Draft',
                        Submission::STATUS_DIAJUKAN => 'Diajukan',
                        Submission::STATUS_DIKEMBALIKAN => 'Dikembalikan',
                        Submission::STATUS_DISETUJUI => 'Disetujui',
                        Submission::STATUS_DITOLAK => 'Ditolak',
                    ])
                    ->multiple()
                    ->placeholder('Pilih status'),
                
                SelectFilter::make('kp_type_id')
                    ->label('Jenis KP')
                    ->relationship('kpType', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih jenis KP'),
                
                Filter::make('submitted_at')
                    ->label('Periode Pengajuan')
                    ->form([
                        DatePicker::make('submitted_from')
                            ->label('Dari Tanggal')
                            ->displayFormat('d/m/Y')
                            ->timezone('Asia/Makassar'),
                        DatePicker::make('submitted_until')
                            ->label('Sampai Tanggal')
                            ->displayFormat('d/m/Y')
                            ->timezone('Asia/Makassar'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['submitted_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('submitted_at', '>=', $date),
                            )
                            ->when(
                                $data['submitted_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('submitted_at', '<=', $date),
                            );
                    }),
                
                SelectFilter::make('region_id')
                    ->label('Wilayah')
                    ->relationship('region', 'city_name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->visible(fn () => Auth::user()?->hasAnyRole(['admin', 'operator_kanwil']))
                    ->placeholder('Pilih wilayah'),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->actions([
                Action::make('edit')
                    ->label('Ubah')
                    ->icon('heroicon-o-pencil-square')
                    ->color('secondary')
                    ->url(fn(Submission $record) => static::getUrl('edit', ['record' => $record]))
                    ->visible(fn(Submission $record) => static::canEditRecord($record, Auth::user())),
                Action::make('submit')
                    ->label(fn(Submission $record) => $record->status === Submission::STATUS_DIKEMBALIKAN ? 'Ajukan Ulang' : 'Ajukan')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(function (Submission $record) {
                        $user = Auth::user();

                        // Bisa ajukan jika masih draft
                        if ($record->status === Submission::STATUS_DRAFT) {
                            return true;
                        }

                        // Bisa ajukan ulang jika dikembalikan dan ini pengusul asli
                        if ($record->status === Submission::STATUS_DIKEMBALIKAN && $user && $user->id === $record->user_id) {
                            return true;
                        }

                        return false;
                    })
                    ->action(function (Submission $record) {
                        // Eager load relationships untuk menghindari N+1 query
                        $record->loadMissing(['kpType', 'documents', 'region']);
                        
                        if (!static::hasAllRequiredDocuments($record)) {
                            Notification::make()->title('Lengkapi semua dokumen persyaratan sebelum ajukan')->danger()->send();
                            return;
                        }

                        $record->update([
                            'status' => Submission::STATUS_DIAJUKAN,
                            'submitted_at' => now(),
                        ]);

                        app(AuditLogger::class)->log($record, 'submit');

                        // Notifikasi ke verifikator (admin/kanwil)
                        $verifikators = \App\Models\User::whereHas('roles', function ($q) {
                            $q->whereIn('name', ['admin', 'operator_kanwil']);
                        })->get();

                        foreach ($verifikators as $verifikator) {
                            $regionName = $record->region->city_name ?? '-';
                            Notification::make()
                                ->title('Pengajuan Baru')
                                ->body("Pengajuan KP untuk {$record->applicant_name} dari {$regionName} telah diajukan")
                                ->success()
                                ->sendToDatabase($verifikator);
                        }
                    }),
                Action::make('return')
                    ->label('Kembalikan')
                    ->color('danger')
                    ->form([
                        Textarea::make('verifikator_notes')->label('Catatan')->required(),
                    ])
                    ->visible(fn(Submission $record) => $record->status === Submission::STATUS_DIAJUKAN && Auth::user()?->hasAnyRole(['admin', 'operator_kanwil']))
                    ->action(function (Submission $record, array $data) {
                        // Eager load user untuk menghindari N+1 query
                        $record->loadMissing('user');
                        
                        $record->update([
                            'status' => Submission::STATUS_DIKEMBALIKAN,
                            'returned_at' => now(),
                            'verifikator_notes' => $data['verifikator_notes'],
                        ]);

                        app(AuditLogger::class)->log($record, 'return', ['notes' => $data['verifikator_notes']]);

                        // Notifikasi ke user yang membuat pengajuan
                        if ($record->user) {
                            Notification::make()
                                ->title('Pengajuan Dikembalikan')
                                ->body("Pengajuan KP untuk {$record->applicant_name} telah dikembalikan. Catatan: {$data['verifikator_notes']}")
                                ->danger()
                                ->sendToDatabase($record->user);
                        }
                    }),
                Action::make('approve')
                    ->label('Setujui')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(Submission $record) => $record->status === Submission::STATUS_DIAJUKAN && Auth::user()?->hasAnyRole(['admin', 'operator_kanwil']))
                    ->action(function (Submission $record) {
                        // Eager load user untuk menghindari N+1 query
                        $record->loadMissing('user');
                        
                        $record->update([
                            'status' => Submission::STATUS_DISETUJUI,
                            'approved_at' => now(),
                        ]);

                        app(AuditLogger::class)->log($record, 'approve');

                        // Notifikasi ke user yang membuat pengajuan
                        if ($record->user) {
                            Notification::make()
                                ->title('Pengajuan Disetujui')
                                ->body("Pengajuan KP untuk {$record->applicant_name} telah disetujui")
                                ->success()
                                ->sendToDatabase($record->user);
                        }
                    }),
                Action::make('reject')
                    ->label('Tolak')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn(Submission $record) => $record->status === Submission::STATUS_DIAJUKAN && Auth::user()?->hasAnyRole(['admin', 'operator_kanwil']))
                    ->action(function (Submission $record) {
                        // Eager load user untuk menghindari N+1 query
                        $record->loadMissing('user');
                        
                        $record->update([
                            'status' => Submission::STATUS_DITOLAK,
                            'rejected_at' => now(),
                        ]);

                        app(AuditLogger::class)->log($record, 'reject');

                        // Notifikasi ke user yang membuat pengajuan
                        if ($record->user) {
                            Notification::make()
                                ->title('Pengajuan Ditolak')
                                ->body("Pengajuan KP untuk {$record->applicant_name} telah ditolak")
                                ->danger()
                                ->sendToDatabase($record->user);
                        }
                    }),
                Action::make('previewDocuments')
                    ->label('Lihat Dokumen')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn(Submission $record) => "Dokumen - {$record->applicant_name}")
                    ->modalWidth('7xl')
                    ->modalContent(function (Submission $record) {
                        // Eager load relationships untuk menghindari N+1 query
                        $record->loadMissing(['documents', 'kpType']);
                        
                        $documents = $record->documents;
                        if ($documents->isEmpty()) {
                            return new \Illuminate\Support\HtmlString('<p class="text-gray-500">Belum ada dokumen yang diunggah.</p>');
                        }
                        
                        $requirements = collect($record->kpType?->document_requirements ?? [])
                            ->keyBy('key');
                        
                        return view('filament.components.pdf-viewer', [
                            'documents' => $documents,
                            'requirements' => $requirements,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->visible(fn(Submission $record) => $record->documents->isNotEmpty()),
                Action::make('downloadZip')
                    ->label('ZIP Berkas')
                    ->color('secondary')
                    ->icon('heroicon-o-archive-box')
                    ->visible(fn(Submission $record) => static::canDownload($record))
                    ->action(fn(Submission $record) => static::downloadZip($record)),
                Action::make('changeStatus')
                    ->label('Ubah Status')
                    ->color('secondary')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->visible(fn() => Auth::user()?->hasRole('admin'))
                    ->form([
                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                Submission::STATUS_DRAFT => 'Draft',
                                Submission::STATUS_DIAJUKAN => 'Diajukan',
                                Submission::STATUS_DIKEMBALIKAN => 'Dikembalikan',
                                Submission::STATUS_DISETUJUI => 'Disetujui',
                                Submission::STATUS_DITOLAK => 'Ditolak',
                            ]),
                        Textarea::make('verifikator_notes')
                            ->label('Catatan (opsional)')
                            ->placeholder('Catatan perubahan status'),
                    ])
                    ->action(function (Submission $record, array $data) {
                        $status = $data['status'];
                        $now = Carbon::now();

                        $record->fill([
                            'status' => $status,
                            'verifikator_notes' => $data['verifikator_notes'] ?? null,
                        ]);

                        $record->submitted_at = $status === Submission::STATUS_DIAJUKAN ? $now : null;
                        $record->returned_at = $status === Submission::STATUS_DIKEMBALIKAN ? $now : null;
                        $record->approved_at = $status === Submission::STATUS_DISETUJUI ? $now : null;
                        $record->rejected_at = $status === Submission::STATUS_DITOLAK ? $now : null;

                        $record->save();

                        app(AuditLogger::class)->log($record, 'change_status', [
                            'status' => $status,
                            'notes' => $data['verifikator_notes'] ?? null,
                        ]);
                    }),
                Action::make('deleteSubmission')
                    ->label('Hapus')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->visible(fn() => Auth::user()?->hasRole('admin'))
                    ->action(function (Submission $record) {
                        static::deleteWithFiles($record);
                        Notification::make()->title('Pengajuan dihapus')->success()->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('submit')
                    ->label('Ajukan')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        // Eager load relationships untuk menghindari N+1 query
                        $records->loadMissing(['user', 'kpType', 'documents']);
                        
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record->status === Submission::STATUS_DRAFT && static::hasAllRequiredDocuments($record)) {
                                $record->update([
                                    'status' => Submission::STATUS_DIAJUKAN,
                                    'submitted_at' => now(),
                                ]);
                                
                                app(AuditLogger::class)->log($record, 'submit');
                                
                                // Notifikasi ke user yang membuat pengajuan
                                if ($record->user) {
                                    Notification::make()
                                        ->title('Pengajuan Dikirim')
                                        ->body("Pengajuan KP untuk {$record->applicant_name} telah dikirim")
                                        ->success()
                                        ->sendToDatabase($record->user);
                                }
                                
                                $count++;
                            }
                        }
                        
                        Notification::make()
                            ->title('Bulk Submit Berhasil')
                            ->body("{$count} pengajuan berhasil diajukan")
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => Auth::user()?->hasAnyRole(['admin', 'operator_kabkota', 'operator_kanwil'])),
                
                BulkAction::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        // Eager load relationships untuk menghindari N+1 query
                        $records->loadMissing(['user']);
                        
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record->status === Submission::STATUS_DIAJUKAN) {
                                $record->update([
                                    'status' => Submission::STATUS_DISETUJUI,
                                    'approved_at' => now(),
                                ]);
                                
                                app(AuditLogger::class)->log($record, 'approve');
                                
                                // Notifikasi ke user yang membuat pengajuan
                                if ($record->user) {
                                    Notification::make()
                                        ->title('Pengajuan Disetujui')
                                        ->body("Pengajuan KP untuk {$record->applicant_name} telah disetujui")
                                        ->success()
                                        ->sendToDatabase($record->user);
                                }
                                
                                $count++;
                            }
                        }
                        
                        Notification::make()
                            ->title('Bulk Approve Berhasil')
                            ->body("{$count} pengajuan berhasil disetujui")
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => Auth::user()?->hasAnyRole(['admin', 'operator_kanwil'])),
                
                BulkAction::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        // Eager load relationships untuk menghindari N+1 query
                        $records->loadMissing(['user']);
                        
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record->status === Submission::STATUS_DIAJUKAN) {
                                $record->update([
                                    'status' => Submission::STATUS_DITOLAK,
                                    'rejected_at' => now(),
                                ]);
                                
                                app(AuditLogger::class)->log($record, 'reject');
                                
                                // Notifikasi ke user yang membuat pengajuan
                                if ($record->user) {
                                    Notification::make()
                                        ->title('Pengajuan Ditolak')
                                        ->body("Pengajuan KP untuk {$record->applicant_name} telah ditolak")
                                        ->danger()
                                        ->sendToDatabase($record->user);
                                }
                                
                                $count++;
                            }
                        }
                        
                        Notification::make()
                            ->title('Bulk Reject Berhasil')
                            ->body("{$count} pengajuan berhasil ditolak")
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => Auth::user()?->hasAnyRole(['admin', 'operator_kanwil'])),
                
                BulkAction::make('exportZip')
                    ->label('Export ZIP')
                    ->icon('heroicon-o-archive-box')
                    ->color('secondary')
                    ->requiresConfirmation()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        return static::downloadBulkZip($records);
                    })
                    ->visible(fn () => Auth::user()?->hasAnyRole(['admin', 'operator_kanwil'])),
                
                BulkAction::make('delete')
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        $count = 0;
                        foreach ($records as $record) {
                            if (Auth::user()?->hasRole('admin') || 
                                ($record->status === Submission::STATUS_DRAFT && Auth::user()?->id === $record->user_id)) {
                                static::deleteWithFiles($record);
                                $count++;
                            }
                        }
                        
                        Notification::make()
                            ->title('Bulk Delete Berhasil')
                            ->body("{$count} pengajuan berhasil dihapus")
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => Auth::user()?->hasRole('admin')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubmissions::route('/'),
            'create' => Pages\CreateSubmission::route('/create'),
            'edit' => Pages\EditSubmission::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        // Filter hanya KP (bukan gelar) - exclude kp_type dengan prefix gelar_
        $kpTypeIds = KpType::query()
            ->where('code', 'not like', 'gelar_%')
            ->pluck('id');
        
        $query->whereIn('kp_type_id', $kpTypeIds);

        // Eager load relationships untuk menghindari N+1 query
        $query->with(['kpType', 'region', 'user', 'documents']);

        if ($user?->hasRole('operator_kabkota')) {
            return $query->where('region_id', $user->region_id);
        }

        return $query;
    }

    protected static function documentFields(?int $kpTypeId): array
    {
        $kpType = $kpTypeId ? KpType::find($kpTypeId) : null;
        $requirements = $kpType?->document_requirements ?? [];

        return collect($requirements)->map(function ($req) {
            $maxSizeKb = isset($req['max_size']) ? (int) ceil($req['max_size'] / 1024) : null;
            $mimes = $req['mimes'] ?? [];
            if (is_string($mimes)) {
                $mimes = array_filter(array_map('trim', explode(',', $mimes)));
            }
            $accepted = collect($mimes ?: [])->map(fn($mime) => $mime === 'pdf' ? 'application/pdf' : $mime)->all();

            return FileUpload::make("documents.{$req['key']}")
                ->label($req['label'] ?? $req['key'])
                ->disk('private')
                ->directory('submissions/temp')
                ->acceptedFileTypes($accepted)
                ->maxSize($maxSizeKb)
                ->required()
                ->visibility('private')
                ->downloadable();
        })->values()->all();
    }

    protected static function canEditRecord(Submission $record, $user): bool
    {
        if ($user?->hasRole('admin')) {
            return true;
        }

        if ($record->status === Submission::STATUS_DRAFT || $record->status === Submission::STATUS_DIKEMBALIKAN) {
            if ($user?->hasRole('operator_kanwil')) {
                return true;
            }
            if ($user?->hasRole('operator_kabkota')) {
                return $record->region_id === $user->region_id;
            }
        }

        return false;
    }

    protected static function canDownload(Submission $record): bool
    {
        $user = Auth::user();
        if ($user?->hasAnyRole(['admin', 'operator_kanwil'])) {
            return true;
        }

        return $user?->hasRole('operator_kabkota') && $record->region_id === $user->region_id;
    }

    public static function syncDocuments(Submission $record, array $documents): void
    {
        if (empty($documents)) {
            return;
        }

        $existingKeys = array_keys($documents);

        // Remove old docs not in incoming set
        $record->documents()
            ->whereNotIn('document_type', $existingKeys)
            ->each(function (SubmissionDocument $doc) {
                Storage::disk('private')->delete($doc->path);
                $doc->delete();
            });

        foreach ($documents as $key => $path) {
            if (!$path) {
                continue;
            }

            $targetDir = "submissions/{$record->id}";
            $fileName = $key.'-'.basename($path);
            $targetPath = $targetDir.'/'.$fileName;

            if (!Storage::disk('private')->exists($targetPath)) {
                Storage::disk('private')->makeDirectory($targetDir);
                Storage::disk('private')->move($path, $targetPath);
            }

            $mime = Storage::disk('private')->mimeType($targetPath) ?: '';
            $size = Storage::disk('private')->size($targetPath) ?: 0;

            SubmissionDocument::updateOrCreate(
                ['submission_id' => $record->id, 'document_type' => $key],
                [
                    'path' => $targetPath,
                    'original_name' => basename($targetPath),
                    'mime_type' => $mime,
                    'size' => $size,
                ]
            );
        }
    }

    protected static function hasAllRequiredDocuments(Submission $record): bool
    {
        // Eager load relationships untuk menghindari N+1 query
        $record->loadMissing(['kpType', 'documents']);
        
        $requirements = $record->kpType?->document_requirements ?? [];
        $requiredKeys = collect($requirements)->pluck('key')->filter()->values();

        if ($requiredKeys->isEmpty()) {
            return true;
        }

        // Gunakan collection yang sudah di-load daripada query baru
        $uploadedKeys = $record->documents->pluck('document_type');

        return $requiredKeys->diff($uploadedKeys)->isEmpty();
    }

    protected static function downloadZip(Submission $record)
    {
        // Eager load relationships untuk menghindari N+1 query
        $record->loadMissing(['kpType', 'documents']);
        
        $zipName = 'pengajuan-'.$record->id.'.zip';
        $tmpPath = storage_path('app/tmp/'.$zipName);
        if (!is_dir(dirname($tmpPath))) {
            mkdir(dirname($tmpPath), 0777, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($tmpPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Notification::make()->title('Gagal membuat ZIP')->danger()->send();
            return null;
        }

        $requirements = collect($record->kpType?->document_requirements ?? [])
            ->keyBy('key');

        foreach ($record->documents as $doc) {
            $label = $requirements[$doc->document_type]['label'] ?? $doc->document_type;
            $folder = trim($record->applicant_name ?: 'Pegawai', '/');
            $zipPath = "{$folder}/{$label}/".basename($doc->path);

            $fullPath = Storage::disk('private')->path($doc->path);
            if (is_file($fullPath)) {
                $zip->addFile($fullPath, $zipPath);
            }
        }

        $zip->close();

        app(AuditLogger::class)->log($record, 'download_zip');

        return response()->download($tmpPath)->deleteFileAfterSend(true);
    }

    protected static function deleteWithFiles(Submission $record): void
    {
        // Eager load documents jika belum di-load
        $record->loadMissing('documents');
        
        $record->documents->each(function (SubmissionDocument $doc) {
            Storage::disk('private')->delete($doc->path);
            $doc->delete();
        });

        $record->delete();
    }

    protected static function downloadBulkZip(\Illuminate\Database\Eloquent\Collection $records)
    {
        if ($records->isEmpty()) {
            Notification::make()->title('Tidak ada data untuk diekspor')->warning()->send();
            return null;
        }

        // Eager load relationships untuk menghindari N+1 query
        $records->loadMissing(['kpType', 'region', 'documents']);

        $zipName = 'pengajuan-kp-bulk-' . now()->format('Y-m-d-His') . '.zip';
        $tmpPath = storage_path('app/tmp/' . $zipName);
        if (!is_dir(dirname($tmpPath))) {
            mkdir(dirname($tmpPath), 0777, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($tmpPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Notification::make()->title('Gagal membuat ZIP')->danger()->send();
            return null;
        }

        foreach ($records as $record) {
            if (!static::canDownload($record)) {
                continue;
            }

            $requirements = collect($record->kpType?->document_requirements ?? [])
                ->keyBy('key');

            $regionName = $record->region?->city_name ?? 'Unknown';
            $folder = "{$regionName}/{$record->applicant_name}";

            foreach ($record->documents as $doc) {
                $label = $requirements[$doc->document_type]['label'] ?? $doc->document_type;
                $zipPath = "{$folder}/{$label}/" . basename($doc->path);

                $fullPath = Storage::disk('private')->path($doc->path);
                if (is_file($fullPath)) {
                    $zip->addFile($fullPath, $zipPath);
                }
            }
        }

        $zip->close();

        app(AuditLogger::class)->log(null, 'download_bulk_zip', [
            'count' => $records->count(),
        ]);

        return response()->download($tmpPath)->deleteFileAfterSend(true);
    }
}

