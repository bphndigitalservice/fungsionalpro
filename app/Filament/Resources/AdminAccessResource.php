<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\AdminAccessResource\Pages\ListAdminAccesses;
use App\Filament\Resources\AdminAccessResource\Pages\CreateAdminAccess;
use App\Filament\Resources\AdminAccessResource\Pages\EditAdminAccess;
use App\Enums\SystemRole;
use App\Filament\Resources\AdminAccessResource\Pages;
use App\Models\AdminAccess;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;

class AdminAccessResource extends Resource
{
    protected static ?string $model = AdminAccess::class;

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
                        // Existing User & Role Group
                        Group::make()
                            ->schema([
                                Select::make('c_role_id')
                                    ->label(__('labels.form.crole.fields.role_name'))
                                    ->searchable()
                                    ->relationship('role', 'role_name')
                                    ->preload()
                                    ->required(),
                                Select::make('user_id')
                                    ->label(__('labels.form.user.fields.name'))
                                    ->searchable()
                                    ->relationship('user', 'name', modifyQueryUsing: fn () => User::role([SystemRole::Admin->value]))
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
                    ->label(__('labels.form.user.fields.name'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('role.role_name')
                    ->label(__('labels.table.crole.name'))
                    ->badge()
                    ->sortable(),

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
            ])
            ->recordActions([
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
            'index' => ListAdminAccesses::route('/'),
            'create' => CreateAdminAccess::route('/create'),
            'edit' => EditAdminAccess::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): string
    {
        return __('labels.nav.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('Akses Admin');
    }
}
