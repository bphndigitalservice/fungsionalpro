<?php

namespace App\Filament\Clusters\Reference\Resources;

use App\Filament\Clusters\Reference;
use App\Filament\Clusters\Reference\Resources\RegRegencyResource\Pages;
use App\Filament\Clusters\Reference\Resources\RegRegencyResource\RelationManagers;
use App\Models\RegRegency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RegRegencyResource extends Resource
{
    protected static ?string $model = RegRegency::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = Reference::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('province_id')
                    ->relationship('province', 'name')
                    ->required(),
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
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('province.name')
                    ->numeric()
                    ->sortable(),
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
            RelationManagers\Echelon1sRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegRegencies::route('/'),
            'create' => Pages\CreateRegRegency::route('/create'),
            'view' => Pages\ViewRegRegency::route('/{record}'),
            'edit' => Pages\EditRegRegency::route('/{record}/edit'),
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
