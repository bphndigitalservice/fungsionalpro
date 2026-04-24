<?php

namespace App\Filament\Pages;

use App\Enums\SystemRole;
use App\Filament\Widgets\PointOverview;
use App\Filament\Widgets\ClientsByRoleChart;
use App\Livewire\ProfileCompletionWidget;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    protected static string $view = 'filament.pages.dashboard';

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ??
            static::$title ??
            __('filament-panels::pages/dashboard.title');
    }

    public static function getNavigationIcon(): string|Htmlable|null
    {
        return static::$navigationIcon
            ?? FilamentIcon::resolve('panels::pages.dashboard.navigation-item')
            ?? (Filament::hasTopNavigation() ? 'heroicon-m-home' : 'heroicon-o-home');
    }

    public static function getRoutePath(): string
    {
        return static::$routePath;
    }

    public function getWidgets(): array
    {
        return [
            ...$this->clientWidgets(),
            ...$this->adminWidgets(),
        ];
    }

    public function clientWidgets(): array
    {
        if (auth()->user()->hasSystemRole(SystemRole::Client)) {
            return [
                ProfileCompletionWidget::class,
                PointOverview::class,
            ];
        }

        return [];
    }

    public function adminWidgets(): array
    {
        $user = auth()->user();
        $widgets = [];

        if ((method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) || $user->hasAnySystemRole(SystemRole::Admin, SystemRole::AdminRegional, SystemRole::AdminPusat, SystemRole::Verifier, SystemRole::AdminInstansi)) {
            $widgets[] = ClientsByRoleChart::class;
        }

        return $widgets;
    }

    public function getVisibleWidgets(): array
    {
        return $this->filterVisibleWidgets($this->getWidgets());
    }

    public function getColumns(): int|string|array
    {
        return 4;
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('filament-panels::pages/dashboard.title');
    }
}
