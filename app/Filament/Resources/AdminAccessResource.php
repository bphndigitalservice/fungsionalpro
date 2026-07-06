<?php

namespace App\Filament\Resources;

use App\Enums\SystemRole;
use App\Filament\Resources\AdminAccessResource\Pages;
use App\Models\AdminAccess;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        // Existing User & Role Group
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Select::make('c_role_id')
                                    ->label(__('labels.form.crole.fields.role_name'))
                                    ->searchable()
                                    ->relationship('role', 'role_name')
                                    ->preload()
                                    ->required(),
                                Forms\Components\Select::make('user_id')
                                    ->label(__('labels.form.user.fields.name'))
                                    ->searchable()
                                    ->relationship('user', 'name', modifyQueryUsing: fn () => User::role([SystemRole::Admin->value]))
                                    ->preload()
                                    ->required(),
                            ])->columns(2),

                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\MorphToSelect::make('accessible')
                                    ->types([
                                        Forms\Components\MorphToSelect\Type::make(RegDepartment::class)
                                            ->titleAttribute('name'),
                                        Forms\Components\MorphToSelect\Type::make(RegProvince::class)
                                            ->titleAttribute('name'),
                                        Forms\Components\MorphToSelect\Type::make(RegRegency::class)
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
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('labels.form.user.fields.name'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('role.role_name')
                    ->label(__('labels.table.crole.name'))
                    ->badge()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('accessible.name')
                    ->label(__('Ruang Regional'))
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
            ])
            ->actions([
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
            'index' => Pages\ListAdminAccesses::route('/'),
            'create' => Pages\CreateAdminAccess::route('/create'),
            'edit' => Pages\EditAdminAccess::route('/{record}/edit'),
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
