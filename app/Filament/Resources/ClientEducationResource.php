<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ClientEducationResource\Pages\ListClientEducation;
use App\Filament\Resources\ClientEducationResource\Pages\CreateClientEducation;
use App\Filament\Resources\ClientEducationResource\Pages\EditClientEducation;
use App\Concerns\Filament\ChecksPhotoUpload;
use App\Enums\EducationLevel;
use App\Filament\Resources\ClientEducationResource\Pages;
use App\Models\Client;
use App\Models\ClientEducation;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ClientEducationResource extends Resource
{
    use ChecksPhotoUpload;

    protected static ?string $model = ClientEducation::class;

    protected static ?string $navigationLabel = 'Riwayat Pendidikan';

    protected static ?string $modelLabel = 'Riwayat Pendidikan';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->heading(__('labels.form.client.heading.client_education'))
                    ->description(__('labels.form.client.heading.client_education_description'))
                    ->schema([
                        Group::make()
                            ->schema([
                                Select::make('level')
                                    ->label(__('labels.form.client.fields.education_level'))
                                    ->options(EducationLevel::class)
                                    ->required(),

                                TextInput::make('university_name')
                                    ->label(__('Universitas'))
                                    ->required(),

                                TextInput::make('program_name')
                                    ->label(__('labels.form.client.fields.program_name'))
                                    ->required(),

                                TextInput::make('gpa')
                                    ->label(__('labels.form.client.fields.gpa'))
                                    ->numeric()
                                    ->maxValue(4)
                                    ->required(),

                                DatePicker::make('certificate_date')
                                    ->label('Tanggal Ijazah')
                                    ->native(false)
                                    ->required(),
                            ])
                            ->columns(3),

                        Group::make()
                            ->schema([
                                FileUpload::make('certificate')
                                    ->disk('s3')
                                    ->label(__('labels.form.client.fields.certificate'))
                                    ->required()
                                    ->maxFiles(1)
                                    ->acceptedFileTypes(config('fungsional-pro.accepted_document_type'))
                                    ->maxSize(config('fungsional-pro.max_upload_file_size'))
                                    ->directory('education_certificate')
                                    ->visibility('private')
                                    ->downloadable()
                                    ->helperText('Format file: PDF | Ukuran maksimal: 750 KB'),

                                FileUpload::make('title_inclusion_file')
                                    ->disk('s3')
                                    ->label('Lembar Pencantuman Gelar')
                                    ->maxFiles(1)
                                    ->acceptedFileTypes(config('fungsional-pro.accepted_document_type'))
                                    ->maxSize(config('fungsional-pro.max_upload_file_size'))
                                    ->directory('title_inclusion_files')
                                    ->visibility('private')
                                    ->downloadable()
                                    ->helperText('Format file: PDF | Ukuran maksimal: 750 KB'),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpan([
                        'lg' => fn(?ClientEducation $record) => $record === null ? 3 : 2,
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('level')
                    ->label(__('labels.form.client.fields.education_level'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('university_name')
                    ->label(__('labels.form.client.fields.university_name'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('program_name')
                    ->label(__('labels.form.client.fields.program_name'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('gpa')
                    ->label(__('labels.form.client.fields.gpa'))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('certificate_date')
                    ->label('Tanggal Ijazah')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('level')
                    ->label(__('labels.form.client.fields.education_level'))
                    ->options(EducationLevel::class),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat Detail')
                    ->modalHeading('Detail Riwayat Pendidikan'),

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
        return [];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('labels.nav.client_menu');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClientEducation::route('/'),
            'create' => CreateClientEducation::route('/create'),
            'edit' => EditClientEducation::route('/{record}/edit'),
        ];
    }

    public static function getRoutePath(): string
    {
        return '/c/educations';
    }
}
