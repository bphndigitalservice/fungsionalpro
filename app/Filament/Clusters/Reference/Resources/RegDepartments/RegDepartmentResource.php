<?php

namespace App\Filament\Clusters\Reference\Resources\RegDepartments;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\Reference\Resources\RegDepartments\RelationManagers\Echelon1sRelationManager;
use App\Filament\Clusters\Reference\Resources\RegDepartments\Pages\ListRegDepartments;
use App\Filament\Clusters\Reference\Resources\RegDepartments\Pages\CreateRegDepartment;
use App\Filament\Clusters\Reference\Resources\RegDepartments\Pages\ViewRegDepartment;
use App\Filament\Clusters\Reference\Resources\RegDepartments\Pages\EditRegDepartment;
use App\Filament\Clusters\Reference\ReferenceCluster;
use App\Filament\Clusters\Reference\Resources\RegDepartments\Pages;
use App\Filament\Clusters\Reference\Resources\RegDepartments\RelationManagers;
use App\Models\RegDepartment;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RegDepartmentResource extends Resource
{
    protected static ?string $model = RegDepartment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = ReferenceCluster::class;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
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
            Echelon1sRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegDepartments::route('/'),
            'create' => CreateRegDepartment::route('/create'),
            'view' => ViewRegDepartment::route('/{record}'),
            'edit' => EditRegDepartment::route('/{record}/edit'),
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
