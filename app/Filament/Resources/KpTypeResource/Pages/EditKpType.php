<?php

namespace App\Filament\Resources\KpTypeResource\Pages;

use App\Filament\Resources\KpTypeResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditKpType extends EditRecord
{
    protected static string $resource = KpTypeResource::class;

    public function getTitle(): string
    {
        return 'Ubah Jenis KP';
    }

    public function getHeading(): string
    {
        return 'Ubah Jenis KP';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['document_requirements'] = collect($data['document_requirements'] ?? [])
            ->map(function ($item) {
                $label = $item['label'] ?? '';
                $key = $item['key'] ?? Str::slug($label) ?: Str::random(6);

                $sizeNumber = (float) ($item['size_number'] ?? 0);
                $unit = strtoupper($item['size_unit'] ?? 'MB');
                $unit = in_array($unit, ['KB', 'MB']) ? $unit : 'MB';
                $multiplier = $unit === 'KB' ? 1024 : 1024 * 1024;
                $maxSize = (int) max(1, ceil($sizeNumber * $multiplier));

                $mimes = $item['allowed_types'] ?? [];
                if (is_string($mimes)) {
                    $mimes = array_filter(array_map('trim', explode(',', $mimes)));
                }

                return [
                    'key' => $key,
                    'label' => $label,
                    'max_size' => $maxSize,
                    'mimes' => array_values($mimes),
                ];
            })
            ->values()
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['document_requirements'] = collect($data['document_requirements'] ?? [])
            ->map(function ($item) {
                $maxSize = (int) ($item['max_size'] ?? 0);
                $isKb = $maxSize > 0 && $maxSize < 1024 * 1024;
                $sizeUnit = $isKb ? 'KB' : 'MB';
                $divisor = $isKb ? 1024 : 1024 * 1024;
                $sizeNumber = $maxSize > 0 ? round($maxSize / $divisor, 2) : null;

                return [
                    'key' => $item['key'] ?? null,
                    'label' => $item['label'] ?? null,
                    'allowed_types' => $item['mimes'] ?? [],
                    'size_number' => $sizeNumber,
                    'size_unit' => $sizeUnit,
                ];
            })
            ->values()
            ->all();

        return $data;
    }
}

