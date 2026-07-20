<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Fieldset;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportBulkAction;
use App\Filament\Resources\ClientCompetenceResource\Pages\ListClientCompetences;
use App\Filament\Resources\ClientCompetenceResource\Pages\CreateClientCompetence;
use App\Filament\Resources\ClientCompetenceResource\Pages\ViewClientCompetence;
use App\Filament\Resources\ClientCompetenceResource\Pages\EditClientCompetence;
use App\Concerns\Filament\ChecksPhotoUpload;
use App\Enums\TrainingCompletionStatus;
use App\Filament\Resources\ClientCompetenceResource\Pages;
use App\Models\Client;
use App\Models\ClientCompetence;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ClientCompetenceResource extends Resource
{
    use ChecksPhotoUpload;

    protected static ?string $model = ClientCompetence::class;
    protected static ?string $navigationLabel = 'Diklat/Pelatihan';
    protected static ?string $modelLabel = 'Diklat/Pelatihan';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
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
                        Select::make('promotion_training_level_id')
                            ->relationship('promotionLevel', 'level', function (Builder $query) {
                                $query->where("c_role_id", Client::current()->c_role_id)->orderBy('id');
                            })->hidden(fn(Get $get) => $get('category') !== "PROMOTION_TRAINING")
                            ->required(fn(Get $get) => $get('category') === "PROMOTION_TRAINING")
                            ->label('Jenjang Diklat Fungsional'),
                        TextInput::make('certificate_number')
                            ->label('Nomor Sertifikat')
                            ->required(),

                        ToggleButtons::make('completion_status')
                            ->label('Status')
                            ->options(TrainingCompletionStatus::class)
                            ->inline()
                            ->required(),
                        TextInput::make('jam_pelajaran')
                            ->label('Jam Pelajaran (JP)')
                            ->numeric()
                            ->minValue(0)
                            ->placeholder('Masukkan total JP')
                            ->suffix('JP'),
                        Fieldset::make()
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
                TextColumn::make('title')
                    ->label('Nama Pelatihan')
                    ->searchable(),
                TextColumn::make('category')
                    ->label('Jenis Diklat/Pelatihan')
                    ->sortable(),
                TextColumn::make('promotionLevel.level')
                    ->label('Jenjang Diklat Fungsional')
                    ->sortable(),
                TextColumn::make('jam_pelajaran')
                    ->label('Jam Pelajaran')
                    ->formatStateUsing(fn ($state) => ($state && $state > 0) ? $state . ' JP' : ''),
                TextColumn::make('certificate_number')
                    ->label('Nomor Sertifikat')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('completion_status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('start_period')
                    ->label('Awal Pelatihan')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_period')
                    ->label('Selesai Pelatihan')
                    ->date()
                    ->sortable(),
                TextColumn::make('institution')
                    ->label('Lembaga Pelatihan')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Jenis Diklat/Pelatihan')
                    ->options([
                        'PROMOTION_TRAINING' => 'Diklat Fungsional',
                        'TECHNICAL_TRAINING' => 'Diklat/Pelatihan Teknis',
                    ]),
                SelectFilter::make('completion_status')
                    ->label('Status')
                    ->options(collect(TrainingCompletionStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])->toArray()),
            ])
            ->recordActions([
                MediaAction::make()
                    ->media(fn(Model $record) => Storage::temporaryUrl($record->competence_file, now()->addMinutes(10)))
                    ->label('Sertifikat'),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                ExportBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClientCompetences::route('/'),
            'create' => CreateClientCompetence::route('/create'),
            'view' => ViewClientCompetence::route('/{record}'),
            'edit' => EditClientCompetence::route('/{record}/edit'),
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
}
