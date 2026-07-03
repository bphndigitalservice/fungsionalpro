<?php

namespace App\Filament\Pages\Client\Point;

use App\Enums\PointSubmissionStatus;
use App\Filament\Pages\Client\Point\Actions\ViewPointSubmission;
use App\Models\Client;
use App\Models\ClientPointSubmission;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Facades\Filament;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Resources\Concerns\HasTabs;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Storage;

class ClientPointList extends Page implements HasInfolists, HasTable
{
    use CanUseDatabaseTransactions;
    use HasPageShield, InteractsWithInfolists;
    use HasTabs;
    use InteractsWithTable {
        makeTable as makeBaseTable;
    }

    protected static string $view = 'filament.pages.client-client-point-list';

    public function mount(): void
    {
        static::canView();

        $client = Client::current();
        if ($client && $client->identity?->photo === null) {
            abort(403);
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        $client = Client::current();
        if ($client) {
            return $client->identity?->photo !== null;
        }
        return true;
    }

    public function table(Table $table): Table
    {
        return $table->query($this->getTableQuery())
            ->columns([
                TextColumn::make('id')->toggleable(isToggledHiddenByDefault: true)->searchable(),
                TextColumn::make('date_of_pak')->date(),
                TextColumn::make('submission_type')->badge(),

                TextColumn::make('status')->searchable(),
                TextColumn::make('is_approved')
                    ->label('Rejected/Accepted')
                    ->state(function (Model $record) {
                        return ($record->status == PointSubmissionStatus::Verified || $record->status == PointSubmissionStatus::ShouldRevise)
                            ? $record->is_approved
                            : 'Sedang diverifikasi';
                    })
                    ->tooltip(fn (Model $record): ?string => $record->verifier_note)
                    ->copyable()
                    ->copyableState(fn (Model $record) => $record->verifier_note),
            ])->actions([
                ViewPointSubmission::make(),

                Action::make('Update')
                    ->hidden(fn (Model $record) => $record->status != PointSubmissionStatus::ShouldRevise)
                    ->url(fn (Model $record) => ClientPointEdit::getUrl(['pointSubmission' => $record->id])),
            ]);
    }

    public static function canView(): bool
    {
        return Filament::auth()->user()->can(static::getPermissionName());
    }

    protected function getTableQuery(): Builder|Relation|null
    {
        return ClientPointSubmission::query()->with(['client', 'bag'])
            ->where('client_id', Client::current()->id ?? -1);
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
        return '/c/points';
    }
}
