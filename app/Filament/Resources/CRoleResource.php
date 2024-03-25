<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CRoleResource\Pages;
use App\Filament\Resources\CRoleResource\RelationManagers;
use App\Models\CRole;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CRoleResource extends Resource
{
    protected static ?string $model = CRole::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('role_name')
                    ->label(__('labels.table.crole.name'))
                    ->required()
                    ->maxLength(255)
                    ->default('Analis Hukum'),
                Forms\Components\Toggle::make('active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('role_name')
                    ->label(__('labels.table.crole.name'))
                    ->searchable(),
                Tables\Columns\IconColumn::make('active')
                    ->label(__('labels.table.crole.active'))
                    ->boolean(),
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
            RelationManagers\LevelRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCRoles::route('/'),
            'create' => Pages\CreateCRole::route('/create'),
            'view' => Pages\ViewCRole::route('/{record}'),
            'edit' => Pages\EditCRole::route('/{record}/edit'),
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
