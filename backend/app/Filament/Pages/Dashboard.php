<?php

namespace App\Filament\Pages;

use App\Enums\Role;
use Filament\Facades\Filament;

class Dashboard extends \Filament\Pages\Dashboard
{
    public function getView(): string
    {
        return Filament::auth()->user()?->role === Role::Admin
            ? 'filament.pages.admin-dashboard'
            : parent::getView();
    }
}
