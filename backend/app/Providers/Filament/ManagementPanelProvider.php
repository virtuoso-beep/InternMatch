<?php

namespace App\Providers\Filament;

use App\Enums\Role;
use App\Filament\Pages\Administration;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Students\StudentResource;
use App\Filament\Widgets\PlacementStatistics;
use App\Http\Middleware\RequirePrivacyConsent;
use Filament\Facades\Filament;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ManagementPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel->default()->id('manage')->path('manage')->login()
            ->brandName('InternMatch Administration')
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('images/umtc-seal.png'))
            // Styling only: the UM maroon, Public Sans (as in the portal) and a small stylesheet; no build step.
            ->colors(['primary' => [
                50 => 'oklch(0.97 0.012 20)', 100 => 'oklch(0.94 0.025 20)', 200 => 'oklch(0.88 0.05 20)',
                300 => 'oklch(0.78 0.085 20)', 400 => 'oklch(0.66 0.12 20)', 500 => 'oklch(0.52 0.135 18)',
                600 => 'oklch(0.42 0.12 18)', 700 => 'oklch(0.35 0.11 18)', 800 => 'oklch(0.29 0.09 18)',
                900 => 'oklch(0.24 0.07 18)', 950 => 'oklch(0.18 0.05 18)',
            ]])
            ->font('Public Sans', provider: GoogleFontProvider::class)
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => new HtmlString('<link rel="stylesheet" href="'.e(asset('css/manage-theme.css')).'">'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->pages([Dashboard::class, Administration::class])
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                if (Filament::auth()->user()?->role === Role::Admin) {
                    return $builder->items([...Dashboard::getNavigationItems(), ...StudentResource::getNavigationItems(), ...Administration::getNavigationItems()]);
                }

                $items = Dashboard::getNavigationItems();
                foreach (Filament::getResources() as $resource) {
                    if ($resource::canAccess() && $resource::shouldRegisterNavigation()) {
                        $items = [...$items, ...$resource::getNavigationItems()];
                    }
                }

                return $builder->items($items);
            })
            ->widgets([AccountWidget::class, PlacementStatistics::class])
            ->middleware([
                EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class,
                AuthenticateSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class,
                SubstituteBindings::class, DisableBladeIconComponents::class, DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class, RequirePrivacyConsent::class], isPersistent: true);
    }
}
