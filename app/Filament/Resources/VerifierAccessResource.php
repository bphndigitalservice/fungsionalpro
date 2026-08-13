<?php

namespace App\Filament\Resources;

use App\Enums\SystemRole;
use App\Filament\Resources\VerifierAccessResource\Pages;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;
use App\Models\User;
use App\Models\VerifierAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class VerifierAccessResource extends Resource
{
    public const SCOPE_BPHN = 'bphn';

    public const SCOPE_REGIONAL = 'regional';

    public const BPHN_SCOPE_LABEL = 'Verifikator BPHN';

    protected static ?string $model = VerifierAccess::class;

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasSystemRole(SystemRole::SuperAdmin) ?? false;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->hasSystemRole(SystemRole::SuperAdmin) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()?->hasSystemRole(SystemRole::SuperAdmin) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->hasSystemRole(SystemRole::SuperAdmin) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Select::make('c_role_id')
                                    ->searchable()
                                    ->relationship('role', 'role_name')
                                    ->preload()
                                    ->required(),
                                Forms\Components\Select::make('user_id')
                                    ->searchable()
                                    ->relationship('user', 'name', modifyQueryUsing: fn () => User::role([SystemRole::Verifier->value, SystemRole::AdminRegional->value]))
                                    ->preload()
                                    ->required(),
                            ])->columns(2),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Select::make('scope_kind')
                                    ->label(__('Ruang Regional'))
                                    ->options([
                                        self::SCOPE_BPHN => self::BPHN_SCOPE_LABEL,
                                        self::SCOPE_REGIONAL => __('Verifikator Instansi'),
                                    ])
                                    ->required()
                                    ->live(),
                                Forms\Components\MorphToSelect::make('accessible')
                                    ->label(__('Instansi'))
                                    ->types([
                                        Forms\Components\MorphToSelect\Type::make(RegDepartment::class)
                                            ->titleAttribute('name'),
                                        Forms\Components\MorphToSelect\Type::make(RegProvince::class)
                                            ->titleAttribute('name'),
                                        Forms\Components\MorphToSelect\Type::make(RegRegency::class)
                                            ->titleAttribute('name'),
                                    ])
                                    ->visible(fn (Get $get): bool => $get('scope_kind') === self::SCOPE_REGIONAL)
                                    ->required(fn (Get $get): bool => $get('scope_kind') === self::SCOPE_REGIONAL),
                            ])->columns(2),
                    ]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function formDataForRegionalScope(array $data): array
    {
        if (($data['scope_kind'] ?? null) === self::SCOPE_BPHN) {
            $data['entity_type'] = null;
            $data['entity_id'] = null;
        }

        unset($data['scope_kind']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function formDataBeforeFill(array $data): array
    {
        $data['scope_kind'] = blank($data['entity_id'] ?? null)
            ? self::SCOPE_BPHN
            : self::SCOPE_REGIONAL;

        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('role.role_name')
                    ->badge()
                    ->label(__('Ruang Jabatan')),
                Tables\Columns\TextColumn::make('accessible.name')
                    ->label(__('Ruang Regional'))
                    ->formatStateUsing(fn (?string $state, VerifierAccess $record): string => $record->isBphnGlobalScope()
                        ? self::BPHN_SCOPE_LABEL
                        : ($state ?? ''))
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVerifierAccesses::route('/'),
            'create' => Pages\CreateVerifierAccess::route('/create'),
            'view' => Pages\ViewVerifierAccess::route('/{record}'),
            'edit' => Pages\EditVerifierAccess::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): string
    {
        return __('labels.nav.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('labels.nav.regional_access');
    }
}
