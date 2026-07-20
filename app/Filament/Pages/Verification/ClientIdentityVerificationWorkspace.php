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
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\JoinClause;

class ClientIdentityVerificationWorkspace extends Page implements HasInfolists, HasTable
{
    use HasCustomPageTab;
    use HasPageShield;
    use InteractsWithInfolists;

    public function mount(): void
    {
        $this->loadDefaultActiveTab();
    }

    public function table(Table $table): Table
    {
        return
            $table
                ->columns([
                    TextColumn::make('id')
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->label(__('labels.table.client.id')),
                    TextColumn::make('nip')
                        ->label(__('labels.table.client.nip'))
                        ->searchable(),
                    TextColumn::make('identity.name')
                        ->label(__('labels.table.client.name'))
                        ->searchable(),
                    TextColumn::make('crole.role_name')
                        ->label(__('labels.table.client.role'))
                        ->numeric()
                        ->sortable(),
                    TextColumn::make('croleLevel.level')
                        ->label(__('labels.table.client.grade'))
                        ->numeric()
                        ->sortable(),
                    TextColumn::make('type')
                        ->label(__('labels.table.client.cluster'))
                        ->searchable(),
                    TextColumn::make('agenciable.name')
                        ->label(__('labels.table.client.agency'))
                        ->searchable()->sortable(),
                    TextColumn::make('echelonable.name')
                        ->label(__('labels.table.client.echelon'))
                        ->searchable()->sortable(),
                    TextColumn::make('echelon_x_text')
                        ->label(__('labels.table.client.echelon_text'))
                        ->searchable()->sortable()->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('status')
                        ->label(__('labels.table.client.status'))
                        ->searchable()->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('assignation_type')
                        ->label(__('labels.table.client.assignation_type'))
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('is_verified')
                        ->label(__('labels.table.client.is_verified'))
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),
                    TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('updated_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('deleted_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->recordActions([
                    ViewClientIdentityAction::make(),
                    AcceptClientIdentityAction::make()->hidden(fn (Model $record) => $record->is_verified),
                    RejectClientIdentityAction::make()->hidden(fn (Model $record) => $record->is_verified),
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
        ])
            ->joinSub($verifierAccess, 'va', function (JoinClause $join) {
                $join->on('clients.c_role_id', '=', 'va.c_role_id');
                $join->on('va.entity_type', '=', 'clients.agency_type');
                $join->on('va.entity_id', '=', 'clients.agency_id');
            })->select('clients.*');
    }

    protected function getUser(): Authenticatable|User
    {
        return auth()->user();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getTabsContentComponent(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
                EmbeddedTable::make(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
            ]);
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('All')),
            'new' => Tab::make(__('New'))
                ->badge($this->getTableQuery()->whereNull('is_verified')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('is_verified')),
            'processed' => Tab::make(__('Processed'))->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('is_verified')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'new';
    }
}
