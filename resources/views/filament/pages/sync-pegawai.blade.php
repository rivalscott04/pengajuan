<x-filament::page>
    <div class="space-y-6">
        {{-- Info Section --}}
        @if($this->totalEmployees !== null)
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Data Pegawai
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Total <span class="font-semibold text-primary-600 dark:text-primary-400">{{ number_format($this->totalEmployees ?? 0, 0, ',', '.') }}</span> pegawai tersedia
                        </p>
                    </div>
                </div>
            </x-filament::section>
        @endif

        @if(($this->totalEmployees ?? 0) === 0)
            <x-filament::card>
                <x-filament::empty-state
                    icon="heroicon-o-user-group"
                    heading="Belum ada data"
                    description="Klik tombol 'Mulai Sync' di atas untuk mengambil dan menyimpan data pegawai ke database."
                />
            </x-filament::card>
        @else
            {{ $this->table }}
        @endif
    </div>
</x-filament::page>
