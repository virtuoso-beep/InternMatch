<?php

namespace App\Filament\Pages;

use App\Enums\Role;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;

class Administration extends Page
{
    protected static ?string $slug = 'administration';

    protected string $view = 'filament.pages.administration';

    #[Url]
    #[Locked]
    public string $section = 'users';

    public const SECTIONS = [
        'chat-settings' => 'Chat Availability',
        'users' => 'User Accounts',
        'roles' => 'Roles & Permissions',
        'hosts' => 'Host Establishments',
        'opportunities' => 'Opportunities',
        'programs' => 'Programs & Curriculum',
        'monitoring-settings' => 'Monitoring Settings',
        'moa' => 'MOA Records',
        'audit' => 'Audit Trail',
        'backup' => 'Backup & Recovery',
        'notifications' => 'Notifications',
        'profile' => 'My Profile',
    ];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user?->role === Role::Admin && $user->canAccessPanel(Filament::getCurrentPanel());
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        abort_unless(array_key_exists($this->section, static::SECTIONS), 404);
    }

    public function getTitle(): string
    {
        return static::SECTIONS[$this->section] ?? 'Management';
    }

    public static function getNavigationItems(): array
    {
        return collect(static::SECTIONS)->map(fn (string $label, string $section) => NavigationItem::make($label)
            ->url(static::getUrl(['section' => $section]))
            ->isActiveWhen(fn () => request()->routeIs(static::getRouteName()) && request('section', 'users') === $section)
        )->values()->all();
    }
}
