<?php

namespace App\Filament\Pages;

use App\Services\EmployeeService;
use App\Models\Employee;
use Filament\Actions;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use BackedEnum;
use UnitEnum;
use Illuminate\Contracts\Auth\Authenticatable;

class SyncPegawai extends Page implements HasTable
{
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Sync Pegawai';
    protected static UnitEnum|string|null $navigationGroup = 'Konfigurasi';
    protected string $view = 'filament.pages.sync-pegawai';

    public static function canAccess(): bool
    {
        /** @var Authenticatable|null $user */
        $user = auth()->user();
        
        return $user && method_exists($user, 'hasRole') && $user->hasRole('admin');
    }

    public ?int $totalEmployees = null;

    public function mount(): void
    {
        $this->totalEmployees = Employee::query()->count();
    }

    public function getTitle(): string
    {
        return 'Sync Pegawai';
    }

    public function getHeading(): string
    {
        return 'Sync Pegawai';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync')
                ->label('Mulai Sync')
                ->color('primary')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    // Perbolehkan proses sync berjalan lebih lama dari 30 detik
                    if (function_exists('set_time_limit')) {
                        @set_time_limit(300);
                    }
                    @ini_set('max_execution_time', '300');

                    /** @var EmployeeService $service */
                    $service = app(EmployeeService::class);
                    
                    // Ambil data pegawai dari API
                    $baseUrl = rtrim(config('services.employee_api.base_url'), '/');
                    if (empty($baseUrl)) {
                        Notification::make()
                            ->title('Konfigurasi belum lengkap')
                            ->body('EMPLOYEE_API_BASE_URL belum diatur di .env')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Ambil semua data sekaligus dari endpoint /all (tanpa paginasi)
                    $response = \Illuminate\Support\Facades\Http::withHeaders($service->headers())
                        ->withoutVerifying()
                        ->acceptJson()
                        ->timeout(300) // Timeout 5 menit karena data bisa banyak
                        ->get($baseUrl.'/api/public/employees/all');

                    if ($response->failed()) {
                        Notification::make()
                            ->title('Gagal terhubung ke API Pegawai')
                            ->body('HTTP '.$response->status().'. Pastikan endpoint benar: '.$baseUrl.'/api/public/employees/all')
                            ->danger()
                            ->send();
                        return;
                    }

                    $json = $response->json();
                    
                    if (!isset($json['success']) || !$json['success']) {
                        Notification::make()
                            ->title('API mengembalikan error')
                            ->body('Format response tidak sesuai atau success=false')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Ambil data dari response - cek berbagai format yang mungkin
                    $items = [];
                    if (isset($json['data'])) {
                        // Format: {success: true, data: [...]} atau {success: true, data: {data: [...]}}
                        if (is_array($json['data'])) {
                            // Cek apakah langsung array atau nested
                            if (isset($json['data'][0]) || empty($json['data'])) {
                                // Langsung array
                                $items = $json['data'];
                            } elseif (isset($json['data']['data']) && is_array($json['data']['data'])) {
                                // Nested dalam data.data
                                $items = $json['data']['data'];
                            } else {
                                // Coba ambil sebagai array langsung
                                $items = is_array($json['data']) ? $json['data'] : [];
                            }
                        }
                    } elseif (is_array($json)) {
                        // Format: langsung array tanpa wrapper
                        $items = $json;
                    }
                    
                    // Pastikan items adalah array
                    if (!is_array($items)) {
                        $items = [];
                    }
                    
                    // Ambil total dari response jika ada (untuk validasi)
                    $totalFromApi = $json['data']['total'] ?? $json['total'] ?? count($items);
                    $totalEmployees = count($items);
                    
                    // Jika jumlah data tidak sesuai dengan total dari API, gunakan pagination sebagai fallback
                    $usePagination = false;
                    if ($totalFromApi > $totalEmployees && $totalFromApi > 0 && $totalEmployees < $totalFromApi * 0.9) {
                        // Jika data kurang dari 90% dari total, gunakan pagination
                        $usePagination = true;
                        Notification::make()
                            ->title('Menggunakan pagination untuk mengambil semua data')
                            ->body('Endpoint /all hanya mengembalikan '.number_format($totalEmployees, 0, ',', '.').' dari '.number_format($totalFromApi, 0, ',', '.').' total. Menggunakan pagination untuk mengambil semua data.')
                            ->info()
                            ->send();
                    }
                    
                    // Jika perlu pagination, ambil semua halaman
                    if ($usePagination) {
                        $items = []; // Reset items
                        $page = 1;
                        $perPage = 1000; // Request banyak per halaman
                        $lastPage = (int) ceil($totalFromApi / $perPage);
                        
                        while ($page <= $lastPage) {
                            $pageResponse = \Illuminate\Support\Facades\Http::withHeaders($service->headers())
                                ->withoutVerifying()
                                ->acceptJson()
                                ->timeout(300)
                                ->get($baseUrl.'/api/public/employees', [
                                    'per_page' => $perPage,
                                    'page' => $page,
                                ]);
                            
                            if ($pageResponse->failed()) {
                                break;
                            }
                            
                            $pageJson = $pageResponse->json();
                            if (!isset($pageJson['success']) || !$pageJson['success']) {
                                break;
                            }
                            
                            $pageItems = $pageJson['data']['data'] ?? [];
                            if (!is_array($pageItems) || empty($pageItems)) {
                                break;
                            }
                            
                            $items = array_merge($items, $pageItems);
                            $page++;
                            
                            // Safety limit
                            if ($page > 100) {
                                break;
                            }
                        }
                        
                        $totalEmployees = count($items);
                    }
                    
                    // Siapkan koleksi untuk di-upsert ke tabel employees
                    $rows = [];

                    $mapItem = function (array $item): array {
                        $nipBaru = $item['NIP_BARU'] ?? null;
                        $nip = $nipBaru ?? ($item['NIP'] ?? null);

                        $tmtPangkat = $item['TMT_PANGKAT'] ?? null;
                        if ($tmtPangkat && str_contains($tmtPangkat, 'T')) {
                            $tmtPangkat = substr($tmtPangkat, 0, 10);
                        }

                        // Encode raw data ke JSON untuk disimpan ke database
                        // Ini adalah cache lengkap dari API
                        $rawJson = json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                        return [
                            'nip' => $nip,
                            'nip_baru' => $nipBaru,
                            'nama_lengkap' => $item['NAMA_LENGKAP'] ?? '',
                            'pangkat_asn' => $item['pangkat_asn'] ?? null,
                            'gol_ruang' => $item['GOL_RUANG'] ?? null,
                            'jabatan' => $item['KET_JABATAN'] ?? null,
                            'satuan_kerja' => $item['SATUAN_KERJA'] ?? null,
                            'kode_satuan_kerja' => $item['KODE_SATUAN_KERJA'] ?? null,
                            'kab_kota' => $item['kab_kota'] ?? null,
                            'jenjang_pendidikan' => $item['JENJANG_PENDIDIKAN'] ?? null,
                            'tmt_pangkat' => $tmtPangkat,
                            'mk_tahun' => (int) ($item['MK_TAHUN'] ?? 0),
                            'mk_bulan' => (int) ($item['MK_BULAN'] ?? 0),
                            'raw' => $rawJson, // Simpan sebagai JSON string (akan di-decode otomatis oleh cast)
                        ];
                    };

                    foreach ($items as $item) {
                        if (! is_array($item)) {
                            continue;
                        }

                        $mapped = $mapItem($item);

                        if (! $mapped['nip']) {
                            continue;
                        }

                        $rows[] = $mapped;
                    }

                    // Simpan ke tabel employees (truncate dulu supaya selalu fresh)
                    // Set employee_id jadi null dulu di submissions untuk menghindari foreign key constraint error
                    DB::table('submissions')->update(['employee_id' => null]);
                    
                    // Disable foreign key checks sementara karena ada foreign key dari submissions
                    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                    Employee::query()->truncate();
                    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                    
                    if (! empty($rows)) {
                        // COPY PASTE dari API ke DB lokal - insert per record untuk handle data besar
                        // Karena data raw bisa sangat besar, insert satu per satu dengan transaction
                        $inserted = 0;
                        $skipped = 0;
                        
                        DB::beginTransaction();
                        try {
                            foreach ($rows as $row) {
                                try {
                                    // Update atau insert per record (COPY PASTE persis dari API)
                                    Employee::query()->updateOrInsert(
                                        ['nip' => $row['nip']],
                                        $row
                                    );
                                    $inserted++;
                                } catch (\Exception $e) {
                                    // Skip record yang error, lanjut ke berikutnya
                                    $skipped++;
                                    continue;
                                }
                            }
                            DB::commit();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            throw $e;
                        }
                        
                        if ($skipped > 0) {
                            Notification::make()
                                ->title('Peringatan')
                                ->body($skipped.' record dilewati karena error')
                                ->warning()
                                ->send();
                        }
                    }

                    $this->totalEmployees = Employee::query()->count();
                    $rowsCount = count($rows);

                    Notification::make()
                        ->title('Berhasil sync data pegawai')
                        ->body('Total: '.number_format($this->totalEmployees, 0, ',', '.').' pegawai tersimpan di database. (Diproses: '.number_format($rowsCount, 0, ',', '.').' dari '.number_format($totalEmployees, 0, ',', '.').' total)')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getTableRecordKey($record): string
    {
        // Gunakan primary key model sebagai key unik
        return (string) ($record->getKey() ?? uniqid());
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('nip_baru')
                    ->label('NIP')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->toggleable(),
                TextColumn::make('nama_lengkap')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->toggleable(),
                TextColumn::make('pangkat_asn')
                    ->label('Pangkat')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('jabatan')
                    ->label('Jabatan')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('satuan_kerja')
                    ->label('Satuan Kerja')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('kab_kota')
                    ->label('Kab/Kota')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('jenjang_pendidikan')
                    ->label('Pendidikan')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('tmt_pangkat')
                    ->label('TMT Pangkat')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('masa_kerja')
                    ->label('Masa Kerja')
                    ->formatStateUsing(function ($record) {
                        $tahun = $record->mk_tahun ?? 0;
                        $bulan = $record->mk_bulan ?? 0;
                        return $tahun.' tahun '.$bulan.' bulan';
                    })
                    ->toggleable(),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('nama_lengkap')
            ->paginated([10, 15, 25, 50, 100])
            ->searchable();
    }

    protected function getTableQuery(): Builder
    {
        return Employee::query();
    }
}

