<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Widgets\AnnouncementBannerWidget;
use App\Filament\Widgets\SubmissionStats;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\HtmlString;

class OperatorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('operator')
            ->path('operator')
            ->login(false) // Login di route terpisah
            ->brandName('KP Kemenag NTB')
            ->favicon(asset('favicon.ico'))
            ->colors(['primary' => Color::Emerald])
            ->sidebarFullyCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AnnouncementBannerWidget::class,
                AccountWidget::class,
                SubmissionStats::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web')
            ->renderHook('panels::body.start', function () {
                $html = '';
                
                // Impersonate banner
                if (session()->has('impersonator_id')) {
                    $user = auth()->user();
                    $name = $user?->name ?? 'Pengguna';
                    $leaveUrl = route('impersonate.leave');
                    $html .= <<<HTML
<div style="padding: 10px 14px;">
    <div style="
        background: #fbbf24;
        color: #fff;
        border-radius: 9999px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 16px;
        font-size: 13px;
        font-weight: 600;
        text-align: center;
        box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    ">
        <span style="font-weight: 600;">
            Mode impersonate aktif — Anda sedang masuk sebagai <span style="font-weight: 700;">{$name}</span>.
        </span>
        <a href="{$leaveUrl}"
           style="
                background: rgba(255,255,255,0.95);
                color: #b45309;
                padding: 7px 12px;
                border-radius: 9999px;
                font-size: 12px;
                font-weight: 700;
                text-decoration: none;
                border: 1px solid rgba(255,255,255,0.6);
                box-shadow: 0 2px 6px rgba(0,0,0,0.06);
           ">
            Keluar impersonate
        </a>
    </div>
</div>
HTML;
                }
                
                // Employee search debug script - inject langsung untuk menghindari timeout
                $html .= <<<'HTML'
<script>
(function() {
    console.log('✅ Employee search debug script loaded');
    
    // Intercept XMLHttpRequest (Filament menggunakan ini)
    const originalOpen = XMLHttpRequest.prototype.open;
    const originalSend = XMLHttpRequest.prototype.send;
    
    XMLHttpRequest.prototype.open = function(method, url, ...rest) {
        this._url = url;
        this._method = method;
        return originalOpen.apply(this, [method, url, ...rest]);
    };
    
    XMLHttpRequest.prototype.send = function(...args) {
        const self = this;
        const requestBody = args[0] || '';
        
        this.addEventListener('load', function() {
            const isSearchRequest = (
                (self._url && (self._url.includes('search') || self._url.includes('livewire'))) ||
                (requestBody && typeof requestBody === 'string' && requestBody.includes('search'))
            );
            
            if (isSearchRequest && this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response && typeof response === 'object') {
                        console.log('🔍 EMPLOYEE SEARCH DEBUG:', {
                            url: self._url,
                            method: self._method,
                            response: response,
                            responseKeys: Object.keys(response),
                            responseCount: Object.keys(response).length
                        });
                    }
                } catch (e) {
                    // Skip non-JSON
                }
            }
        });
        
        return originalSend.apply(this, args);
    };
})();
</script>
HTML;
                
                return new HtmlString($html);
            });
    }
}

