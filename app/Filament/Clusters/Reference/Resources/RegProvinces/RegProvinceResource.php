<?php

namespace App\Filament\Clusters\Reference\Resources\RegProvinces;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\Reference\Resources\RegProvinces\RelationManagers\RegenciesRelationManager;
use App\Filament\Clusters\Reference\Resources\RegProvinces\RelationManagers\Echelon1sRelationManager;
use App\Filament\Clusters\Reference\Resources\RegProvinces\Pages\ListRegProvinces;
use App\Filament\Clusters\Reference\Resources\RegProvinces\Pages\CreateRegProvince;
use App\Filament\Clusters\Reference\Resources\RegProvinces\Pages\ViewRegProvince;
use App\Filament\Clusters\Reference\Resources\RegProvinces\Pages\EditRegProvince;
use App\Filament\Clusters\Reference\ReferenceCluster;
use App\Models\RegProvince;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RegProvinceResource extends Resource
{
    protected static ?string $model = RegProvince::class;

    protected static ?string $cluster = ReferenceCluster::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

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
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
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
            RegenciesRelationManager::class,
            Echelon1sRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegProvinces::route('/'),
            'create' => CreateRegProvince::route('/create'),
            'view' => ViewRegProvince::route('/{record}'),
            'edit' => EditRegProvince::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('labels.nav.references_province');
    }

    public static function getNavigationLabel(): string
    {
        return __('labels.nav.references_province');
    }

    public static function getNavigationBadge(): ?string
    {
        return RegProvince::query()->count();
    }
}
