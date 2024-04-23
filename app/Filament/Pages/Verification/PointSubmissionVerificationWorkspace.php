<?php

namespace App\Filament\Pages\Verification;

use App\Concerns\Components\HasCustomPageTab;
use App\Filament\Pages\Verification\Actions\VerifyPointSubmissionAction;
use App\Models\Client;
use App\Models\ClientPointSubmission;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Facades\Filament;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Pages\Page;
use Filament\Resources\Components\Tab;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class PointSubmissionVerificationWorkspace extends Page implements HasInfolists, HasTable
{
    use HasCustomPageTab;
    use HasPageShield, InteractsWithInfolists;

    protected static string $view = 'filament.pages.verifier-workspace';

    protected User $verifier;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('client.identity.name'),
                TextColumn::make('client.nip'),
                TextColumn::make('bag.label'),
                TextColumn::make('submission_type'),
                TextColumn::make('is_approved'),
                TextColumn::make('verified_at'),
            ])
            ->actions([
                VerifyPointSubmissionAction::make()->hidden(fn (Model $record) => (! auth()->user()->isSuperAdmin() || ! auth()->user()->hasRole(['verifier'])) && is_null($record->verified_at) && is_null($record->revised_at)),
            ]);
    }

    public static function canView(): bool
    {
        return Filament::auth()->user()->can(static::getPermissionName());
    }

    protected function getTableQuery(): Builder|Relation|null
    {
        return ClientPointSubmission::with('client')->leftJoin('clients', 'client_id', '=', 'client_point_submissions.id')
            ->where('clients.agency_type', '=', $this->getVerifier()->entity_type)
            ->where('clients.agency_id', '=', $this->getVerifier()->entity_id)
            ->select('client_point_submissions.*');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Verifikasi');
    }

    public static function getNavigationLabel(): string
    {
        return __('labels.page.v_client_point_verification.title');
    }

    public function getTitle(): string|Htmlable
    {
        return __('labels.page.v_client_point_submission.title');
    }

    public function getVerifier(): User
    {
        $this->verifier = auth()->user();

        return $this->verifier;
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('All')),
            'new' => Tab::make(__('New'))
                ->badge($this->getTableQuery()->whereNull('client_point_submissions.verified_at')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('client_point_submissions.verified_at')),
            'processed' => Tab::make(__('Processed'))->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('client_point_submissions.verified_at')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'new';
    }
}
