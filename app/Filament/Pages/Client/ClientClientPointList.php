<?php

namespace App\Filament\Pages\Client;

use App\Models\ClientPointSubmission;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Concerns\HasUnsavedDataChangesAlert;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Resources\Concerns\HasTabs;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ClientClientPointList extends Page implements HasInfolists, HasTable
{
    use HasPageShield, InteractsWithInfolists;
    use CanUseDatabaseTransactions;
    use HasTabs;
    use InteractsWithTable {
        makeTable as makeBaseTable;
    }

    protected static string $view = 'filament.pages.client-client-point-list';

    public function table(Table $table): Table
    {
        return $table->query($this->getTableQuery())
            ->columns([

            ]);
    }

    public static function canView(): bool
    {
        return Filament::auth()->user()->can(static::getPermissionName()) || auth()->user()->isActiveClient();
    }

    protected function getTableQuery(): Builder|Relation|null
    {
        return ClientPointSubmission::query()->with(['client', 'bag'])->where('client_id', auth()->user()->client->id);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('labels.nav.client_point');
    }

    public static function getNavigationLabel(): string
    {
        return __('labels.page.client_point_list.nav');
    }

    public function getTitle(): string|Htmlable
    {
        return __('labels.page.client_point_list.title');
    }

    public static function getRoutePath(): string
    {
        return "/c/points";
    }

}
