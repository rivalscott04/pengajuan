<?php

namespace App\Filament\Widgets;

use App\Models\Announcement;
use Filament\Widgets\Widget;

class AnnouncementBannerWidget extends Widget
{
    protected string $view = 'filament.widgets.announcement-banner';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 1; // Urutkan di atas widget lain

    protected function getViewData(): array
    {
        $announcements = Announcement::active()
            ->ordered()
            ->get();

        return [
            'announcements' => $announcements,
        ];
    }
}

