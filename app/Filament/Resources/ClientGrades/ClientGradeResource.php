<?php

namespace App\Filament\Resources\ClientGrades;

use App\Filament\Resources\ClientGrades\Pages\CreateClientGrade;
use App\Filament\Resources\ClientGrades\Pages\EditClientGrade;
use App\Filament\Resources\ClientGrades\Pages\ListClientGrades;
use App\Filament\Resources\ClientGrades\Pages\ViewClientGrade;
use App\Models\Client;
use App\Models\ClientGrade;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Hugomyb\FilamentMediaAction\Actions\MediaAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ClientGradeResource extends Resource
{
    protected static ?string $model = ClientGrade::class;

    protected static ?string $navigationLabel = 'Riwayat Pangkat/Golongan';

    protected static ?string $modelLabel = 'Riwayat Pangkat/Golongan';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('reg_grade_id')
                    ->label('Pangkat/Golongan')
                    ->relationship('grade', 'grade_code')
                    ->required(),
                DatePicker::make('effective_date')
                    ->label('TMT Pangkat/Golongan')
                    ->required(),
                TextInput::make('decree_number')
                    ->label('Nomor SK Pangkat/Golongan')
                    ->required()
                    ->maxLength(255),
                FileUpload::make('decree_file')
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
                TextColumn::make('grade.grade_code')
                    ->label('Pangkat/Golongan')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('effective_date')
                    ->label('TMT')
                    ->date()
                    ->sortable(),
                TextColumn::make('decree_number')
                    ->label('Nomor SK')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                MediaAction::make()
                    ->media(fn (Model $record) => Storage::temporaryUrl($record->decree_file, now()->addMinutes(10)))
                    ->label('SK Pangkat/Golongan'),
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
            'index' => ListClientGrades::route('/'),
            'create' => CreateClientGrade::route('/create'),
            'view' => ViewClientGrade::route('/{record}'),
            'edit' => EditClientGrade::route('/{record}/edit'),
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
