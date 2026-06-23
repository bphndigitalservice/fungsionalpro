<?php

namespace App\Filament\Resources;

use App\Enums\TrainingCompletionStatus; // Make sure this is imported
use App\Filament\Resources\ClientCompetenceResource\Pages;
use App\Models\Client;
use App\Models\ClientCompetence;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ClientCompetenceResource extends Resource
{
    protected static ?string $model = ClientCompetence::class;
    protected static ?string $navigationLabel = 'Diklat/Pelatihan';
    protected static ?string $modelLabel = 'Diklat/Pelatihan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        TextInput::make('title')
                            ->label('Nama Pelatihan')
                            ->columnSpanFull()
                            ->required(),
                        ToggleButtons::make('category')
                            ->label('Jenis Diklat/Pelatihan')
                            ->live()
                            ->options([
                                'PROMOTION_TRAINING' => 'Diklat Fungsional',
                                'TECHNICAL_TRAINING' => 'Diklat/Pelatihan Teknis',
                            ])->inline()->required(),
                        Forms\Components\Select::make('promotion_training_level_id')
                            ->relationship('promotionLevel', 'level', function (Builder $query) {
                                $query->where("c_role_id", Client::current()->c_role_id)->orderBy('id');
                            })->hidden(fn(Forms\Get $get) => $get('category') !== "PROMOTION_TRAINING")
                            ->required(fn(Forms\Get $get) => $get('category') === "PROMOTION_TRAINING")
                            ->label('Jenjang Diklat Fungsional'),
                        TextInput::make('certificate_number')
                            ->label('Nomor Sertifikat')
                            ->required(),

                        // Dynamically pulled everything directly from your Enum setup!
                        ToggleButtons::make('completion_status')
                            ->label('Status')
                            ->options(TrainingCompletionStatus::class)
                            ->inline()
                            ->required(),

                        Forms\Components\Fieldset::make()
                            ->label('Waktu Pelaksanaan Pelatihan/Diklat')
                            ->schema([
                                DatePicker::make('start_period')
                                    ->label('Mulai')
                                    ->required(),
                                DatePicker::make('end_period')
                                    ->label('Selesai')
                                    ->required(),
                            ])->columns(2),
                        TextInput::make('institution')
                            ->label('Lembaga Pelatihan')
                            ->required(),
                        FileUpload::make('competence_file')
                            ->disk('s3')
                            ->label('Sertifikat')
                            ->acceptedFileTypes(config('fungsional-pro.accepted_document_type'))
                            ->maxFiles(1)
                            ->maxSize(config('fungsional-pro.max_upload_file_size'))
                            ->visibility(config('fungsional-pro.s3.visibility'))
                            ->directory('competence-files')
                            ->downloadable()
                            ->required()
                            ->helperText('Format file: PDF | Ukuran maksimal: 750 KB'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Nama Pelatihan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Jenis Diklat/Pelatihan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('promotionLevel.level')
                    ->label('Jenjang Diklat Fungsional')
                    ->sortable(),
                Tables\Columns\TextColumn::make('certificate_number')
                    ->label('Nomor Sertifikat')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completion_status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_period')
                    ->label('Awal Pelatihan')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_period')
                    ->label('Selesai Pelatihan')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('institution')
                    ->label('Lembaga Pelatihan')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Jenis Diklat/Pelatihan')
                    ->options([
                        'PROMOTION_TRAINING' => 'Diklat Fungsional',
                        'TECHNICAL_TRAINING' => 'Diklat/Pelatihan Teknis',
                    ]),
                Tables\Filters\SelectFilter::make('completion_status')
                    ->label('Status')
                    ->options(TrainingCompletionStatus::class),
            ])
            ->actions([
                MediaAction::make()
                    ->media(fn(Model $record) => Storage::temporaryUrl($record->competence_file, now()->addMinutes(10)))
                    ->label('Sertifikat'),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\ExportBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
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

    public static function getRoutePath(): string
    {
        return '/c/courses';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Client::current() !== null;
    }
}
