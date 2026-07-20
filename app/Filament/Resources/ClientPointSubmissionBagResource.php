<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ClientPointSubmissionBagResource\Pages\ListClientPointSubmissionBags;
use App\Filament\Resources\ClientPointSubmissionBagResource\Pages\CreateClientPointSubmissionBag;
use App\Filament\Resources\ClientPointSubmissionBagResource\Pages\ViewClientPointSubmissionBag;
use App\Filament\Resources\ClientPointSubmissionBagResource\Pages\EditClientPointSubmissionBag;
use App\Enums\PointSubmissionPeriod;
use App\Filament\Resources\ClientPointSubmissionBagResource\Pages;
use App\Models\ClientPointSubmissionBag;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClientPointSubmissionBagResource extends Resource
{
    protected static ?string $model = ClientPointSubmissionBag::class;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('label')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255),
                        Select::make('rules')
                            ->label(__('Rules'))
                            ->options(PointSubmissionPeriod::class)
                            ->multiple(),
                        Toggle::make('is_enabled')
                            ->required(),
                    ])->columnSpan(5),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                TextColumn::make('label')
                    ->label(__('Name'))
                    ->searchable(),
                IconColumn::make('is_enabled')
                    ->boolean(),
                TextColumn::make('rules'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClientPointSubmissionBags::route('/'),
            'create' => CreateClientPointSubmissionBag::route('/create'),
            'view' => ViewClientPointSubmissionBag::route('/{record}'),
            'edit' => EditClientPointSubmissionBag::route('/{record}/edit'),
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
