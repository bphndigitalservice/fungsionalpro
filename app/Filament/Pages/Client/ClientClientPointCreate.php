<?php

namespace App\Filament\Pages\Client;

use App\Concerns\Point\SubmissionRule;
use App\Models\Client;
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
use Illuminate\Contracts\Support\Htmlable;

class ClientClientPointCreate extends Page implements HasForms, HasInfolists
{
    use HasPageShield, HasUnsavedDataChangesAlert, InteractsWithForms, InteractsWithInfolists, InteractsWithFormActions;
    use CanUseDatabaseTransactions;

    protected static string $view = 'filament.pages.client-client-point-create';

    protected ClientPointSubmission $record;
    public ?array $data = [];
    public string $previousUrl;

    public function mount(): void
    {

    }

    public function getBreadcrumbs(): array
    {
        return [
            ''
        ];
    }

    public static function canView(): bool
    {
        return Filament::auth()->user()->can(static::getPermissionName()) || auth()->user()->isActiveClient();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('labels.nav.client_point');
    }

    public static function getNavigationLabel(): string
    {
        return __('labels.page.client_point_create.nav');
    }

    public function getTitle(): string|Htmlable
    {
        return __('labels.page.client_point_create.title');
    }

    public function isAllowedToSubmit(): bool
    {
        return SubmissionRule::hasSubmissionActive();
    }

    public static function getRoutePath(): string
    {
        return "/c/points/create";
    }


}
