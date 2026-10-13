<?php

namespace App\Providers\Filament;

use App\Enums\Role;
use App\Filament\Pages\Administration;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Students\StudentResource;
use App\Filament\Widgets\PlacementStatistics;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ManagementPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel->default()->id('manage')->path('manage')->login()
            ->brandName('InternMatch Administration')
            ->colors(['primary' => Color::Rose])
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
            ->authMiddleware([Authenticate::class], isPersistent: true);
    }
}
