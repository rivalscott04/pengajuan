<?php

namespace App\Exports;

use App\Models\Submission;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Database\Eloquent\Builder;

class SubmissionExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function query()
    {
        // Pastikan relationships sudah di-eager load untuk menghindari N+1 query
        return $this->query->with(['kpType', 'region']);
    }

    public function headings(): array
    {
        return [
            'No',
            'NIP',
            'Nama Pegawai',
            'Jenis KP',
            'Wilayah',
            'Status',
            'Pangkat Sekarang',
            'Pangkat Target',
            'Golongan Target',
            'Satuan Kerja',
            'Jabatan',
            'Tanggal Submit',
            'Tanggal Disetujui',
            'Tanggal Dikembalikan',
            'Catatan Verifikator',
        ];
    }

    public function map($submission): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $submission->nip,
            $submission->applicant_name,
            $submission->kpType->name ?? '-',
            $submission->region->city_name ?? '-',
            match($submission->status) {
                Submission::STATUS_DRAFT => 'Draft',
                Submission::STATUS_DIAJUKAN => 'Diajukan',
                Submission::STATUS_DIKEMBALIKAN => 'Dikembalikan',
                Submission::STATUS_DISETUJUI => 'Disetujui',
                Submission::STATUS_DITOLAK => 'Ditolak',
                default => $submission->status,
            },
            $submission->pangkat_sekarang ?? '-',
            $submission->pangkat_target ?? '-',
            $submission->golongan_target ?? '-',
            $submission->satuan_kerja ?? '-',
            $submission->jabatan ?? '-',
            $submission->submitted_at ? $submission->submitted_at->format('d/m/Y H:i') : '-',
            $submission->approved_at ? $submission->approved_at->format('d/m/Y H:i') : '-',
            $submission->returned_at ? $submission->returned_at->format('d/m/Y H:i') : '-',
            $submission->verifikator_notes ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E3F2FD'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}

