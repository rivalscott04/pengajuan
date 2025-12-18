<?php

namespace App\Filament\Widgets;

use App\Models\Submission;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SubmissionStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected function getCards(): array
    {
        $query = Submission::query();
        $user = Auth::user();

        if ($user?->hasRole('operator_kabkota')) {
            $query->where('region_id', $user->region_id);
        }

        $countDraft = (clone $query)->where('status', Submission::STATUS_DRAFT)->count();
        $countPending = (clone $query)->where('status', Submission::STATUS_DIAJUKAN)->count();
        $countReturned = (clone $query)->where('status', Submission::STATUS_DIKEMBALIKAN)->count();
        $countApproved = (clone $query)->where('status', Submission::STATUS_DISETUJUI)->count();
        $countRejected = (clone $query)->where('status', Submission::STATUS_DITOLAK)->count();
        $countTotal = (clone $query)->count();

        return [
            Stat::make('Total Pengajuan', $countTotal)
                ->description('Semua pengajuan')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('primary'),
            Stat::make('Draft', $countDraft)
                ->description('Belum diajukan')
                ->descriptionIcon('heroicon-m-document')
                ->color('gray'),
            Stat::make('Diajukan', $countPending)
                ->description('Menunggu verifikasi')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make('Dikembalikan', $countReturned)
                ->description('Perlu perbaikan')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color('danger'),
            Stat::make('Disetujui', $countApproved)
                ->description('Pengajuan diterima')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Ditolak', $countRejected)
                ->description('Pengajuan ditolak')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
}

