<?php

namespace App\Filament\Resources\ClientDossiers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Filament\Resources\ClientDossiers\Pages\ListClientDossiers;
use App\Filament\Resources\ClientDossiers\Pages\CreateClientDossier;
use App\Filament\Resources\ClientDossiers\Pages\ViewClientDossier;
use App\Filament\Resources\ClientDossiers\Pages\EditClientDossier;
use App\Filament\Resources\ClientDossiers\Pages;
use App\Filament\Resources\ClientDossiers\RelationManagers;
use App\Models\Client;
use App\Models\ClientDossier;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClientDossierResource extends Resource
{
    protected static ?string $model = ClientDossier::class;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('client_document_type_id')
                    ->label('Jenis Dokumen')
                    ->relationship('documentType', 'type')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('doc_number')
                    ->label('Nomor Dokumen')
                    ->required()
                    ->maxLength(255),

                DatePicker::make('doc_date')
                    ->label('Tanggal Dokumen')
                    ->required(),

                Textarea::make('note')
                    ->label('Catatan')
                    ->columnSpanFull(),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
            TextColumn::make('documentType.type')
                ->label('Jenis Dokumen')
                ->searchable()
                ->sortable(),

            TextColumn::make('doc_number')
                ->label('Nomor Dokumen')
                ->searchable()
                ->wrap(),

            TextColumn::make('doc_date')
                ->label('Tanggal')
                ->date()
                ->sortable(),

            TextColumn::make('note')
                ->label('Catatan')
                ->limit(50)
                ->wrap(),
            ])
            ->filters([
                
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
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
            'index' => ListClientDossiers::route('/'),
            'create' => CreateClientDossier::route('/create'),
            'view' => ViewClientDossier::route('/{record}'),
            'edit' => EditClientDossier::route('/{record}/edit'),
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
