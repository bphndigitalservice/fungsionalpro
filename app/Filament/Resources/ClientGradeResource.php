<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientGradeResource\Pages;
use App\Filament\Resources\ClientGradeResource\RelationManagers;
use App\Models\Client;
use App\Models\ClientGrade;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClientGradeResource extends Resource
{
    protected static ?string $model = ClientGrade::class;

    //protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('reg_grade_id')
                    ->required()
                    ->numeric(),
                Forms\Components\DatePicker::make('effective_date')
                    ->required(),
                Forms\Components\TextInput::make('decree_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('decree_file')
                    ->required()
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
                Tables\Columns\TextColumn::make('reg_grade_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('effective_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('decree_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
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
            'index' => Pages\ListClientGrades::route('/'),
            'create' => Pages\CreateClientGrade::route('/create'),
            'view' => Pages\ViewClientGrade::route('/{record}'),
            'edit' => Pages\EditClientGrade::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): string
    {
        return __('labels.nav.client_menu');
    }

    public static function getNavigationLabel(): string
    {
        return __('Riwayat Pangkat/Golongan');
    }


    public static function getRoutePath(): string
    {
        return '/c/grades';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Client::current() !== null;
    }
}
