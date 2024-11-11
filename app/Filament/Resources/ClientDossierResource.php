<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientDossierResource\Pages;
use App\Filament\Resources\ClientDossierResource\RelationManagers;
use App\Models\Client;
use App\Models\ClientDossier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClientDossierResource extends Resource
{
    protected static ?string $model = ClientDossier::class;

    //protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('client_document_type_id')
                    ->required()
                    ->maxLength(26),
                Forms\Components\TextInput::make('client_id')
                    ->required()
                    ->maxLength(26),
                Forms\Components\Textarea::make('note')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('client_document_type_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('client_id')
                    ->searchable(),
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
                //Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListClientDossiers::route('/'),
            'create' => Pages\CreateClientDossier::route('/create'),
            'view' => Pages\ViewClientDossier::route('/{record}'),
            'edit' => Pages\EditClientDossier::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationGroup(): string
    {
        return __('labels.nav.client_menu');
    }

    public static function getNavigationLabel(): string
    {
        return __('Informasi Pendukung');
    }

    public static function getRoutePath(): string
    {
        return '/c/dossier';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Client::current() !== null;
    }
}
