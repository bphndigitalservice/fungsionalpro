<?php

namespace App\Filament\Resources;

use App\Enums\ClientCluster;
use App\Enums\SystemRole;
use App\Enums\UkomApplicationStatus;
use App\Filament\Resources\UkomApplicationResource\Pages;
use App\Infolists\Components\MinioFileEntry;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;
use App\Models\UkomApplication;
use App\Models\User;
use App\Services\UkomApplicationAccess;
use App\Services\UkomApplicationService;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UkomApplicationResource extends Resource
{
    protected static ?string $model = UkomApplication::class;

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return __('Verifikasi');
    }

    public static function getNavigationLabel(): string
    {
        return __('labels.page.ukom_verification.nav');
    }

    public static function getModelLabel(): string
    {
        return 'Pengajuan Ukom';
    }

    public static function getPluralModelLabel(): string
    {
        return __('labels.page.ukom_verification.nav');
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        return $user && $user->hasAnySystemRole(
            SystemRole::Admin,
            SystemRole::AdminInstansi,
            SystemRole::SuperAdmin,
        );
    }

    public static function canViewAny(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        if ($user === null) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return app(UkomApplicationAccess::class)
            ->scopedQuery($user)
            ->with(['targetCRole', 'user', 'agenciable']);
    }

    public static function canView($record): bool
    {
        $user = Auth::user();

        if ($user === null) {
            return false;
        }

        return app(UkomApplicationAccess::class)
            ->scopedQuery($user)
            ->whereKey($record->getKey())
            ->exists();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Pemohon')
                    ->schema([
                        Infolists\Components\TextEntry::make('nama')->label('Nama'),
                        Infolists\Components\TextEntry::make('nip')->label('NIP'),
                        Infolists\Components\TextEntry::make('current_jabatan')->label('Jabatan Saat Ini'),
                        Infolists\Components\TextEntry::make('targetCRole.role_name')->label('Daftar Sebagai'),
                        Infolists\Components\TextEntry::make('status')->badge(),
                        Infolists\Components\TextEntry::make('agenciable.name')->label('Instansi'),
                        Infolists\Components\TextEntry::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->visible(fn (UkomApplication $record) => filled($record->rejection_reason)),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Dokumen')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('documents')
                            ->schema([
                                Infolists\Components\TextEntry::make('documentType.label')->label('Jenis'),
                                MinioFileEntry::make('file_path')
                                    ->label('Berkas')
                                    ->disk('s3'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')->searchable(),
                Tables\Columns\TextColumn::make('nip')->searchable()->label('NIP'),
                Tables\Columns\TextColumn::make('instansi')
                    ->label('Instansi')
                    ->getStateUsing(fn (UkomApplication $record) => $record->agenciable?->name)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('current_jabatan')->label('Jabatan Saat Ini'),
                Tables\Columns\TextColumn::make('targetCRole.role_name')->label('Daftar Sebagai'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diajukan Pada')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('instansi_decision')
                    ->label('Diterima/Ditolak pada (Instansi)')
                    ->getStateUsing(fn (UkomApplication $record) => $record->instansiDecisionDisplay())
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('pembina_decision')
                    ->label('Diterima/Ditolak pada (Instansi pembina)')
                    ->getStateUsing(fn (UkomApplication $record) => $record->pembinaDecisionDisplay())
                    ->placeholder('-'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('agency_filter')
                    ->form([
                        Forms\Components\Select::make('type')
                            ->label('Tingkat Instansi')
                            ->options(ClientCluster::class)
                            ->live(),
                        Forms\Components\Select::make('agency_id')
                            ->label('Instansi')
                            ->options(function (Forms\Get $get) {
                                return match ($get('type')) {
                                    ClientCluster::Central->value, 'central' => RegDepartment::query()->orderBy('name')->pluck('name', 'id'),
                                    ClientCluster::LocalProvince->value, 'local_province' => RegProvince::query()->orderBy('name')->pluck('name', 'id'),
                                    ClientCluster::LocalRegency->value, 'local_regency' => RegRegency::query()->orderBy('name')->pluck('name', 'id'),
                                    default => [],
                                };
                            })
                            ->searchable(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return static::applyVerificationFilters($query, [
                            'agency_filter' => $data,
                        ]);
                    }),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(fn (): array => static::statusFilterOptions()),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ViewAction::make(),
                static::forwardAction(),
                static::acceptAction(),
                static::rejectAction(),
            ]);
    }

    public static function statusFilterOptions(?User $user = null): array
    {
        $user ??= Auth::user();
        $access = app(UkomApplicationAccess::class);

        // SuperAdmin and admin-instansi keep pending_instansi; pembina (and dual-role-as-admin) do not.
        $includePendingInstansi = $user !== null
            && ($user->isSuperAdmin() || $access->isInstansiOnly($user));

        return collect(UkomApplicationStatus::cases())
            ->reject(fn (UkomApplicationStatus $status) => $status === UkomApplicationStatus::Draft)
            ->reject(fn (UkomApplicationStatus $status) => $status === UkomApplicationStatus::PendingInstansi
                && ! $includePendingInstansi)
            ->mapWithKeys(fn (UkomApplicationStatus $status) => [
                $status->value => $status->getLabel(),
            ])
            ->all();
    }

    /**
     * @param  array{agency_filter?: array{type?: string|null, agency_id?: int|string|null}, status?: array{value?: string|null}|string|null}  $filterData
     */
    public static function applyVerificationFilters(Builder $query, array $filterData): Builder
    {
        $agency = $filterData['agency_filter'] ?? [];
        $type = $agency['type'] ?? null;
        $agencyId = $agency['agency_id'] ?? null;

        if (filled($type)) {
            $query->where('type', $type);
        }

        if (filled($agencyId) && filled($type)) {
            $agencyType = match ($type) {
                ClientCluster::Central->value, 'central' => RegDepartment::class,
                ClientCluster::LocalProvince->value, 'local_province' => RegProvince::class,
                ClientCluster::LocalRegency->value, 'local_regency' => RegRegency::class,
                default => null,
            };

            $query->where('agency_id', $agencyId);

            if ($agencyType !== null) {
                $query->where('agency_type', $agencyType);
            }
        }

        $status = $filterData['status']['value'] ?? $filterData['status'] ?? null;
        if (is_array($status)) {
            $status = $status['value'] ?? null;
        }

        if (filled($status)) {
            $query->where('status', $status);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUkomApplications::route('/'),
            'view' => Pages\ViewUkomApplication::route('/{record}'),
        ];
    }

    public static function forwardAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('forward')
            ->label(__('labels.page.ukom_verification.forward'))
            ->visible(fn (UkomApplication $record) => app(UkomApplicationAccess::class)->canForward(Auth::user(), $record))
            ->requiresConfirmation()
            ->modalHeading(__('labels.page.ukom_verification.forward'))
            ->modalDescription(__('labels.page.ukom_verification.forward_confirm'))
            ->modalSubmitActionLabel(__('labels.page.ukom_verification.forward'))
            ->action(function (UkomApplication $record) {
                app(UkomApplicationService::class)->forward(Auth::user(), $record);
                Notification::make()->success()->title(__('labels.page.ukom_verification.forwarded'))->send();
            });
    }

    public static function acceptAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('accept')
            ->label('Terima')
            ->color('success')
            ->visible(fn (UkomApplication $record) => app(UkomApplicationAccess::class)->canFinalDecide(Auth::user(), $record))
            ->requiresConfirmation()
            ->action(function (UkomApplication $record) {
                app(UkomApplicationService::class)->accept(Auth::user(), $record);
                Notification::make()->success()->title('Pengajuan diterima.')->send();
            });
    }

    public static function rejectAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('reject')
            ->label('Tolak')
            ->color('danger')
            ->visible(fn (UkomApplication $record) => app(UkomApplicationAccess::class)->canForward(Auth::user(), $record)
                || app(UkomApplicationAccess::class)->canFinalDecide(Auth::user(), $record))
            ->form([
                Forms\Components\Textarea::make('rejection_reason')
                    ->label('Alasan')
                    ->required(),
            ])
            ->action(function (UkomApplication $record, array $data) {
                try {
                    app(UkomApplicationService::class)->reject(Auth::user(), $record, $data['rejection_reason']);
                    Notification::make()->success()->title('Pengajuan ditolak.')->send();
                } catch (ValidationException $exception) {
                    Notification::make()->danger()->title(collect($exception->errors())->flatten()->first())->send();
                }
            });
    }
}
