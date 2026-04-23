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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('client_document_type_id')
                    ->label('Jenis Dokumen')
                    ->relationship('documentType', 'type')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('doc_number')
                    ->label('Nomor Dokumen')
                    ->required()
                    ->maxLength(255),

                Forms\Components\DatePicker::make('doc_date')
                    ->label('Tanggal Dokumen')
                    ->required(),

                Forms\Components\Textarea::make('note')
                    ->label('Catatan')
                    ->columnSpanFull(),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
            Tables\Columns\TextColumn::make('documentType.type')
                ->label('Jenis Dokumen')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('doc_number')
                ->label('Nomor Dokumen')
                ->searchable()
                ->wrap(),

            Tables\Columns\TextColumn::make('doc_date')
                ->label('Tanggal')
                ->date()
                ->sortable(),

            Tables\Columns\TextColumn::make('note')
                ->label('Catatan')
                ->limit(50)
                ->wrap(),
            ])
            ->filters([
                
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
