<?php

namespace App\Filament\Resources;

use App\Enums\PointSubmissionPeriod;
use App\Filament\Resources\ClientPointSubmissionBagResource\Pages;
use App\Models\ClientPointSubmissionBag;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClientPointSubmissionBagResource extends Resource
{
    protected static ?string $model = ClientPointSubmissionBag::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('label')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('rules')
                            ->label(__('Rules'))
                            ->options(PointSubmissionPeriod::class)
                            ->multiple(),
                        Forms\Components\Toggle::make('is_enabled')
                            ->required(),
                    ])->columnSpan(5),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('label')
                    ->label(__('Name'))
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_enabled')
                    ->boolean(),
                Tables\Columns\TextColumn::make('rules'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientPointSubmissionBags::route('/'),
            'create' => Pages\CreateClientPointSubmissionBag::route('/create'),
            'view' => Pages\ViewClientPointSubmissionBag::route('/{record}'),
            'edit' => Pages\EditClientPointSubmissionBag::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('labels.nav.client_point_submission_bag');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('labels.nav.client_point');
    }
}
