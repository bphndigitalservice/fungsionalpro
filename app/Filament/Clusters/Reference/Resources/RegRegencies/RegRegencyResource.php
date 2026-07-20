<?php

namespace App\Filament\Clusters\Reference\Resources\RegRegencies;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\Reference\Resources\RegRegencies\RelationManagers\Echelon1sRelationManager;
use App\Filament\Clusters\Reference\Resources\RegRegencies\Pages\ListRegRegencies;
use App\Filament\Clusters\Reference\Resources\RegRegencies\Pages\CreateRegRegency;
use App\Filament\Clusters\Reference\Resources\RegRegencies\Pages\ViewRegRegency;
use App\Filament\Clusters\Reference\Resources\RegRegencies\Pages\EditRegRegency;
use App\Filament\Clusters\Reference\ReferenceCluster;
use App\Filament\Clusters\Reference\Resources\RegRegencies\Pages;
use App\Filament\Clusters\Reference\Resources\RegRegencies\RelationManagers;
use App\Models\RegRegency;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RegRegencyResource extends Resource
{
    protected static ?string $model = RegRegency::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = ReferenceCluster::class;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('province_id')
                    ->relationship('province', 'name')
                    ->required(),
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
                    ->numeric()
                    ->sortable(),
                TextColumn::make('province.name')
                    ->numeric()
                    ->sortable(),
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
            'index' => ListRegRegencies::route('/'),
            'create' => CreateRegRegency::route('/create'),
            'view' => ViewRegRegency::route('/{record}'),
            'edit' => EditRegRegency::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('labels.nav.references_regency');
    }

    public static function getNavigationLabel(): string
    {
        return __('labels.nav.references_regency');
    }

    public static function getNavigationBadge(): ?string
    {
        return RegRegency::query()->count();
    }
}
