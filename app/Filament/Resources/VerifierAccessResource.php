<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\VerifierAccessResource\Pages\ListVerifierAccesses;
use App\Filament\Resources\VerifierAccessResource\Pages\CreateVerifierAccess;
use App\Filament\Resources\VerifierAccessResource\Pages\ViewVerifierAccess;
use App\Filament\Resources\VerifierAccessResource\Pages\EditVerifierAccess;
use App\Enums\SystemRole;
use App\Filament\Resources\VerifierAccessResource\Pages;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;
use App\Models\User;
use App\Models\VerifierAccess;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class VerifierAccessResource extends Resource
{
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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Group::make()
                            ->schema([
                                Select::make('c_role_id')
                                    ->searchable()
                                    ->relationship('role', 'role_name')
                                    ->preload()
                                    ->required(),
                                Select::make('user_id')
                                    ->searchable()
                                    ->relationship('user', 'name', modifyQueryUsing: fn () => User::role([SystemRole::Verifier->value, SystemRole::AdminRegional->value]))
                                    ->preload()
                                    ->required(),
                            ])->columns(2),
                        Group::make()
                            ->schema([
                                MorphToSelect::make('accessible')
                                    ->types([
                                        Type::make(RegDepartment::class)
                                            ->titleAttribute('name'),
                                        Type::make(RegProvince::class)
                                            ->titleAttribute('name'),
                                        Type::make(RegRegency::class)
                                            ->titleAttribute('name'),
                                    ]),
                            ])->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('role.role_name')
                    ->badge()
                    ->label(__('Ruang Jabatan')),
                TextColumn::make('accessible.name')
                    ->label(__('Ruang Regional'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListVerifierAccesses::route('/'),
            'create' => CreateVerifierAccess::route('/create'),
            'view' => ViewVerifierAccess::route('/{record}'),
            'edit' => EditVerifierAccess::route('/{record}/edit'),
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