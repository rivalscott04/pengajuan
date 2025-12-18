@php
    $announcements = $this->getViewData()['announcements'] ?? collect();
@endphp

<div>
    @if($announcements->count() > 0)
        <div style="display: flex; flex-direction: column; gap: 12px;">
            @foreach($announcements as $announcement)
                @php
                    $styles = [
                        'info' => [
                            'bg' => 'linear-gradient(to right, #3b82f6, #2563eb)',
                            'border' => '#60a5fa',
                            'icon' => 'heroicon-o-information-circle',
                        ],
                        'warning' => [
                            'bg' => 'linear-gradient(to right, #f59e0b, #d97706)',
                            'border' => '#fbbf24',
                            'icon' => 'heroicon-o-exclamation-triangle',
                        ],
                        'success' => [
                            'bg' => 'linear-gradient(to right, #10b981, #059669)',
                            'border' => '#34d399',
                            'icon' => 'heroicon-o-check-circle',
                        ],
                        'danger' => [
                            'bg' => 'linear-gradient(to right, #ef4444, #dc2626)',
                            'border' => '#f87171',
                            'icon' => 'heroicon-o-x-circle',
                        ],
                    ];
                    $style = $styles[$announcement->type] ?? $styles['info'];
                @endphp

                <div style="background: {{ $style['bg'] }}; border-left: 5px solid {{ $style['border'] }}; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); padding: 20px 24px; width: 100%; margin: 0;">
                    <div style="display: flex; align-items: flex-start; gap: 16px; width: 100%;">
                        <div style="flex-shrink: 0; margin-top: 2px;">
                            @svg($style['icon'], 'w-6 h-6', ['style' => 'color: rgba(255, 255, 255, 1); font-weight: bold;'])
                        </div>
                        <div style="flex: 1; min-width: 0; width: 100%;">
                            <h3 style="font-size: 18px; font-weight: 700; color: white; margin-bottom: 8px; line-height: 1.3; letter-spacing: -0.01em;">
                                {{ $announcement->title }}
                            </h3>
                            <div style="font-size: 15px; color: white; font-weight: 500; line-height: 1.7; width: 100%;">
                                <style>
                                    .announcement-content * { color: white !important; font-weight: 500 !important; }
                                    .announcement-content p { margin-bottom: 10px; font-weight: 500 !important; }
                                    .announcement-content p:last-child { margin-bottom: 0; }
                                    .announcement-content strong { font-weight: 700 !important; font-size: 16px !important; }
                                    .announcement-content em { font-style: italic; font-weight: 500 !important; }
                                    .announcement-content u { text-decoration: underline; font-weight: 600 !important; }
                                    .announcement-content b { font-weight: 700 !important; }
                                </style>
                                <div class="announcement-content">
                                    {!! $announcement->content !!}
                                </div>
                            </div>
                            @if($announcement->ends_at)
                                <div style="margin-top: 14px; padding-top: 14px; border-top: 1px solid rgba(255, 255, 255, 0.25);">
                                    <p style="font-size: 13px; color: white; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                                        <svg style="width: 15px; height: 15px; font-weight: bold;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span style="font-weight: 600;"><strong>Batas:</strong> {{ $announcement->ends_at->format('d F Y, H:i') }} WITA</span>
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

