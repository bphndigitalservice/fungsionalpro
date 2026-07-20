<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\CRoleResource\RelationManagers\LevelRelationManager;
use App\Filament\Resources\CRoleResource\Pages\ListCRoles;
use App\Filament\Resources\CRoleResource\Pages\CreateCRole;
use App\Filament\Resources\CRoleResource\Pages\ViewCRole;
use App\Filament\Resources\CRoleResource\Pages\EditCRole;
use App\Filament\Resources\CRoleResource\Pages;
use App\Filament\Resources\CRoleResource\RelationManagers;
use App\Models\CRole;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CRoleResource extends Resource
{
    protected static ?string $model = CRole::class;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('role_name')
                    ->label(__('labels.form.crole.fields.role_name'))
                    ->required()
                    ->maxLength(255)
                    ->default('Analis Hukum'),
                Toggle::make('active')
                    ->label(__('labels.form.crole.fields.active'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('role_name')
                    ->label(__('labels.table.crole.name'))
                    ->searchable(),
                IconColumn::make('active')
                    ->label(__('labels.table.crole.active'))
                    ->boolean(),
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
            LevelRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCRoles::route('/'),
            'create' => CreateCRole::route('/create'),
            'view' => ViewCRole::route('/{record}'),
            'edit' => EditCRole::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): string
    {
        return __('labels.nav.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('labels.nav.crole');
    }

    public static function getModelLabel(): string
    {
        return __('labels.nav.crole');
    }
}
