<?php

namespace App\Filament\Pages\Verification;

use App\Concerns\Components\HasCustomPageTab;
use App\Filament\Pages\Verification\Actions\AcceptClientIdentityAction;
use App\Filament\Pages\Verification\Actions\RejectClientIdentityAction;
use App\Filament\Pages\Verification\Actions\ViewClientIdentityAction;
use App\Models\Client;
use App\Models\User;
use App\Models\VerifierAccess;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Facades\Filament;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Pages\Page;
use Filament\Resources\Components\Tab;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\JoinClause;

class ClientIdentityVerificationWorkspace extends Page implements HasInfolists, HasTable
{
    use HasCustomPageTab, HasPageShield, InteractsWithInfolists;

    protected static string $view = 'filament.pages.client-identity-verification-workspace';

    public function mount(): void
    {
        $this->loadDefaultActiveTab();
    }

    public function table(Table $table): Table
    {
        return
            $table
                ->columns([
                    Tables\Columns\TextColumn::make('id')
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->label(__('labels.table.client.id')),
                    Tables\Columns\TextColumn::make('identity.name')
                        ->label('Nama')
                        ->searchable(isIndividual: true),
                    Tables\Columns\TextColumn::make('nip')
                        ->label('NIP')
                        ->searchable(isIndividual: true),
                    Tables\Columns\TextColumn::make('agenciable.name')
                        ->label('Instansi')
                        ->searchable()->sortable(),
                    Tables\Columns\TextColumn::make('echelonable.name')
                        ->label('Unit Kerja')
                        ->searchable()->sortable(),
                    Tables\Columns\TextColumn::make('echelon_x_text')
                        ->label(__('labels.table.client.echelon_text'))
                        ->searchable()->sortable()->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('status')
                        ->label(__('labels.table.client.status'))
                        ->searchable()->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('assignation_type')
                        ->label(__('labels.table.client.assignation_type'))
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('is_verified')
                        ->label('Status Verifikasi')
                        ->state(function (Model $record) {
                            if ($record->is_verified === \App\Enums\Verified::Unverified) {
                                if ($record->note && !empty($record->note->verifier_notes)) {
                                    return 'Rejected';
                                }
                                return 'Belum diproses';
                            }

                            return $record->is_verified;
                        })
                        ->color(function (Model $record) {
                            if ($record->is_verified === \App\Enums\Verified::Unverified) {
                                if ($record->note && !empty($record->note->verifier_notes)) {
                                    return 'danger';
                                }
                                return 'gray';
                            }
                            return null;
                        })
                        ->icon(function (Model $record) {
                            if ($record->is_verified === \App\Enums\Verified::Unverified) {
                                if ($record->note && !empty($record->note->verifier_notes)) {
                                    return 'heroicon-o-x-circle';
                                }
                                return 'heroicon-o-clock';
                            }
                            return null;
                        })
                        ->tooltip(function (Model $record) {
                            if ($record->is_verified === \App\Enums\Verified::Unverified && $record->note && !empty($record->note->verifier_notes)) {
                                return $record->note->verifier_notes;
                            }
                            return null;
                        })
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),
                    Tables\Columns\TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('updated_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('deleted_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->actions([
                    ViewClientIdentityAction::make(),
                ]);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Verifikasi');
    }

    public static function getNavigationLabel(): string
    {
        return __('labels.page.v_client_identity_verification.title');
    }

    /**
     * @return string|null
     */
    public function getTitle(): string
    {
        return __('labels.page.v_client_identity_verification.title');
    }

    public static function canView(): bool
    {
        return Filament::auth()->user()->can(static::getPermissionName());
    }

    protected function getTableQuery(): Builder|Relation|null
    {

        $verifierAccess = VerifierAccess::query()->where('user_id', auth()->user()->id);

        return Client::with([
            'identity',
            'crole',
            'croleLevel',
            'agenciable',
            'echelonable',
            'note',
        ])
            ->where('is_profile_draft', false)
            ->joinSub($verifierAccess, 'va', function (JoinClause $join) {
                $join->on('clients.c_role_id', '=', 'va.c_role_id');
                $join->on('va.entity_type', '=', 'clients.agency_type');
                $join->on('va.entity_id', '=', 'clients.agency_id');
            })->select('clients.*');
    }

    protected function getUser(): \Illuminate\Contracts\Auth\Authenticatable|User
    {
        return auth()->user();
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('All')),
            'new' => Tab::make(__('New'))
                ->badge($this->getTableQuery()->where('is_verified', \App\Enums\Verified::Unverified)->whereDoesntHave('note', fn(Builder $q) => $q->whereNotNull('verifier_notes'))->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_verified', \App\Enums\Verified::Unverified)->whereDoesntHave('note', fn(Builder $q) => $q->whereNotNull('verifier_notes'))),
            'processed' => Tab::make(__('Processed'))->modifyQueryUsing(fn(Builder $query) => $query->where('is_verified', '!=', \App\Enums\Verified::Unverified)->orWhereHas('note', fn(Builder $q) => $q->whereNotNull('verifier_notes'))),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'new';
    }
}
