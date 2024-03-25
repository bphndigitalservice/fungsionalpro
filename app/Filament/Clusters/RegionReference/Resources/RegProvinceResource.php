<?php

namespace App\Filament\Clusters\RegionReference\Resources;

use App\Filament\Clusters\RegionReference;
use App\Filament\Resources\RegProvinceResource\Pages;
use App\Filament\Resources\RegProvinceResource\RelationManagers;
use App\Models\RegProvince;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RegProvinceResource extends Resource
{
    protected static ?string $model = RegProvince::class;
    protected static ?string $cluster = RegionReference::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


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
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
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
            RegionReference\Resources\RegProvinceResource\RelationManagers\RegenciesRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => RegionReference\Resources\RegProvinceResource\Pages\ListRegProvinces::route('/'),
            'create' => RegionReference\Resources\RegProvinceResource\Pages\CreateRegProvince::route('/create'),
            'view' => RegionReference\Resources\RegProvinceResource\Pages\ViewRegProvince::route('/{record}'),
            'edit' => RegionReference\Resources\RegProvinceResource\Pages\EditRegProvince::route('/{record}/edit'),
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
