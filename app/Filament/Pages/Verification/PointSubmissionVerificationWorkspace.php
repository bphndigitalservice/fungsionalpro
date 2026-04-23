<?php

namespace App\Filament\Pages\Verification;

use App\Concerns\Components\HasCustomPageTab;
use App\Enums\PointSubmissionStatus;
use App\Filament\Pages\Verification\Actions\VerifyPointSubmissionAction;
use App\Models\ClientPointSubmission;
use App\Models\User;
use App\Models\VerifierAccess;
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
use Illuminate\Database\Query\JoinClause;

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
                TextColumn::make('client.identity.name')->label('Nama'),
                TextColumn::make('client.nip')->label('NIP'),
                TextColumn::make('client.agenciable.name')->label('Instansi'),
                TextColumn::make('client.echelonable.name')->label('Unit Kerja'),
                TextColumn::make('submission_type')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('is_approved'),
                TextColumn::make('verified_at')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                VerifyPointSubmissionAction::make()->hidden(fn(Model $record) => !static::canVerifying() || $record->status == PointSubmissionStatus::Verified),
            ]);
    }

    public static function canVerifying(): bool
    {
        return auth()->user()->isSuperAdmin() || auth()->user()->hasRole('verifier');
    }

    public static function canView(): bool
    {
        return Filament::auth()->user()->can(static::getPermissionName());
    }

    protected function getTableQuery(): Builder|Relation|null
    {
        $verifierAccess = VerifierAccess::query()->where('user_id', auth()->user()->id);

        return ClientPointSubmission::query()
            ->whereHas('client', function (Builder $query) use ($verifierAccess) {
                $query->whereHas('crole', function (Builder $roleQuery) use ($verifierAccess) {
                    $roleQuery->whereIn('id', $verifierAccess->pluck('c_role_id'));
                })
                    ->whereIn('agency_type', $verifierAccess->pluck('entity_type'))
                    ->whereIn('agency_id', $verifierAccess->pluck('entity_id'));
            });
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


    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('All')),
            'new' => Tab::make(__('New'))
                ->badge($this->getTableQuery()->whereNull('client_point_submissions.verified_at')->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNull('client_point_submissions.verified_at')),
            'processed' => Tab::make(__('Processed'))->modifyQueryUsing(fn(Builder $query) => $query->whereNotNull('client_point_submissions.verified_at')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'new';
    }

    /*public function getTableRecordKey(Model $record): string
    {
        return $record->getKey() ?? uniqid('client_point_submission_');
    }*/


}
