<?php

namespace App\Filament\Pages\Verification;

use App\Concerns\Components\HasCustomPageTab;
use App\Models\ClientActivity;
use App\Models\User;
use App\Models\VerifierAccess;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Facades\Filament;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Pages\Page;
use Filament\Resources\Components\Tab;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\JoinClause;
use Filament\Tables\Concerns\InteractsWithTable;

use App\Filament\Pages\Verification\Actions\ViewClientActivityAction;
use Filament\Resources\Concerns\HasTabs;

class ClientActivityVerificationWorkspace extends Page
implements HasTable, HasInfolists
{
    use
        HasCustomPageTab,
        HasTabs,
        HasPageShield,
        InteractsWithInfolists;

    protected static string $view =
        'filament.pages.client-activity-verification-workspace';


    public function mount(): void
    {
        $this->loadDefaultActiveTab();
    }

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('Verifikasi');
    }

    public static function getNavigationLabel(): string
    {
        return __('Verifikasi Pelaporan Kegiatan');
    }

    public function getTitle(): string
    {
        return __('Verifikasi Pelaporan Kegiatan');
    }

    public static function canView(): bool
    {
        return Filament::auth()
            ->user()
            ->can(static::getPermissionName());
    }


    public function table(Table $table): Table
    {
        return $table

            ->columns([

                Tables\Columns\TextColumn::make('client.identity.name')
                    ->label('Nama'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Nama Kegiatan')
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_period')
                    ->date()
                    ->label('Tanggal'),

                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Verified')
                    ->tooltip(function ($record) {

                            if ($record?->is_verified === \App\Enums\Acceptance::Reject) {
                                return "Alasan Penolakan: {$record->verification_note}";
                            } if ($record?->is_verified === \App\Enums\Acceptance::Accept) {
                                return "Kegiatan telah diverifikasi";
                            }
                            return null;
                        }),

            ])

            ->actions([
                ViewClientActivityAction::make(),
            ]);
    }

    protected function getTableQuery():
    Builder|Relation|null
    {

        $verifierAccess =
            VerifierAccess::query()
            ->where('user_id', auth()->id());

        return ClientActivity::query()

            ->with('client.identity')

            ->join(
                'clients',
                'client_activities.client_id',
                '=',
                'clients.id'
            )

            ->joinSub(
                $verifierAccess,
                'va',
                function (JoinClause $join) {

                    $join->on('clients.c_role_id', '=', 'va.c_role_id')
                        ->on('va.entity_type', '=', 'clients.agency_type')
                        ->on('va.entity_id', '=', 'clients.agency_id');
                }
            )

            ->select('client_activities.*');
    }


    public function getTabs(): array
    {
        return [

            'all' => Tab::make('All'),

            'new' => Tab::make('New')

                ->badge(

                    (clone $this->getTableQuery())
                        ->whereNull(
                            'client_activities.is_verified'
                        )
                        ->count()

                )

                ->modifyQueryUsing(

                    fn (Builder $query) =>

                        $query->whereNull(
                            'client_activities.is_verified'
                        )

                ),

            'processed' => Tab::make('Processed')

                ->modifyQueryUsing(

                    fn (Builder $query) =>

                        $query->whereNotNull(
                            'client_activities.is_verified'
                        )

                ),

        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'new';
    }

    protected function getUser(): User
    {
        return auth()->user();
    }
}
