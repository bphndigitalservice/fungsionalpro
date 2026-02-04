<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientGradeResource\Pages;
use App\Filament\Resources\ClientGradeResource\RelationManagers;
use App\Models\Client;
use App\Models\ClientGrade;
use App\Enums\CRoleAssignation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction;

class ClientGradeResource extends Resource
{
    protected static ?string $model = ClientGrade::class;

    //protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Riwayat Pangkat/Golongan';

    protected static ?string $modelLabel = 'Riwayat Pangkat/Golongan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('reg_grade_id')
                        ->label('Pangkat/Golongan')
                        ->relationship('grade', 'grade_code')
                        ->required(),
                Forms\Components\DatePicker::make('effective_date')
                    ->label('TMT Pangkat/Golongan')
                    ->required(),
                Forms\Components\TextInput::make('decree_number')
                    ->label('Nomor SK Pangkat/Golongan')
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('decree_file')
                            ->disk('s3')
                            ->label('File SK Pangkat/Golongan')
                            ->required()
                            ->maxFiles(1)
                            ->acceptedFileTypes(config('fungsional-pro.accepted_document_type'))
                            ->maxSize(config('fungsional-pro.max_upload_file_size'))
                            ->directory('decree_file')
                            ->visibility('private')
                            ->downloadable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('grade.grade_code')
                    ->label('Pangkat/Golongan')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('effective_date')
                    ->label('TMT')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('decree_number')
                    ->label('Nomor SK')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                MediaAction::make()
                    ->media(fn(Model $record) => Storage::temporaryUrl($record->decree_file, now()->addMinutes(10)))
                    ->label('SK Pangkat/Golongan'),
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


    public static function getRoutePath(): string
    {
        return '/c/grades';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Client::current() !== null;
    }
}
