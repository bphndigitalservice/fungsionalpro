<?php

namespace App\Filament\Clusters\Reference\Resources;

use App\Filament\Clusters\Reference;
use App\Filament\Clusters\Reference\Resources\RegDepartmentResource\Pages;
use App\Filament\Clusters\Reference\Resources\RegDepartmentResource\RelationManagers;
use App\Models\RegDepartment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RegDepartmentResource extends Resource
{
    protected static ?string $model = RegDepartment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = Reference::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
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
            RelationManagers\Echelon1sRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegDepartments::route('/'),
            'create' => Pages\CreateRegDepartment::route('/create'),
            'view' => Pages\ViewRegDepartment::route('/{record}'),
            'edit' => Pages\EditRegDepartment::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('labels.nav.references_department');
    }

    public static function getNavigationLabel(): string
    {
        return __('labels.nav.references_department');
    }

    public static function getNavigationBadge(): ?string
    {
        return RegDepartment::query()->count();
    }
}
