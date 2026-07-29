<?php

namespace App\Filament\Resources;

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

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->heading(__('labels.form.client.heading.client_education'))
                    ->description(__('labels.form.client.heading.client_education_description'))
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Select::make('level')
                                    ->label(__('labels.form.client.fields.education_level'))
                                    ->options(EducationLevel::class)
                                    ->required(),

                                Forms\Components\TextInput::make('university_name')
                                    ->label(__('Universitas'))
                                    ->required(),

                                Forms\Components\TextInput::make('program_name')
                                    ->label(__('labels.form.client.fields.program_name'))
                                    ->required(),

                                Forms\Components\TextInput::make('gpa')
                                    ->label(__('labels.form.client.fields.gpa'))
                                    ->numeric()
                                    ->maxValue(4)
                                    ->required(),

                                Forms\Components\DatePicker::make('certificate_date')
                                    ->label('Tanggal Ijazah')
                                    ->native(false)
                                    ->required(),
                            ])
                            ->columns(3),

                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\FileUpload::make('certificate')
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

                                Forms\Components\FileUpload::make('title_inclusion_file')
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

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('level')
                    ->label(__('Jenjang Pendidikan'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('university_name')
                    ->label(__('Universitas'))
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
                Tables\Filters\SelectFilter::make('level')
                    ->label(__('Jenjang Pendidikan'))
                    ->options(EducationLevel::class),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail')
                    ->modalHeading('Detail Riwayat Pendidikan'),

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
        return [];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('labels.nav.client_menu');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientEducation::route('/'),
            'create' => Pages\CreateClientEducation::route('/create'),
            'edit' => Pages\EditClientEducation::route('/{record}/edit'),
        ];
    }

    public static function getRoutePath(): string
    {
        return '/c/educations';
    }
}
