<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientCompetenceResource\Pages;
use App\Filament\Resources\ClientCompetenceResource\RelationManagers;
use App\Models\ClientCompetence;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use GuzzleHttp\Psr7\UploadedFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use function Laravel\Prompts\text;

class ClientCompetenceResource extends Resource
{
    protected static ?string $model = ClientCompetence::class;

    protected static ?string $navigationLabel = 'Kompetensi';

    protected static ?string $modelLabel = 'Kompetensi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->columnSpanFull()
                    ->required(),
                DatePicker::make('start_period')
                    ->required(),
                DatePicker::make('end_period')
                    ->required(),
                TextInput::make('institution')
                    ->required(),
                FileUpload::make('competence_file')
                    ->acceptedFileTypes(config('fungsional-pro.accepted_document_type'))
                    ->maxFiles(1)   
                    ->downloadable()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_period')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_period')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('institution')
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientCompetences::route('/'),
            'create' => Pages\CreateClientCompetence::route('/create'),
            'view' => Pages\ViewClientCompetence::route('/{record}'),
            'edit' => Pages\EditClientCompetence::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): string
    {
        return __('labels.nav.client_menu');
    }

    private static function storageVisibility(): string
    {
        return config('fungsional-pro.s3.visibility');
    }
}
