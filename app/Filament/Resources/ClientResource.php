<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Models\Client;
use App\Models\Enums\ClientCluster;
use App\Models\Enums\ClientStatus;
use App\Models\Enums\CRoleAssignation;
use App\Models\Enums\Gender;
use App\Models\RegDepartment;
use App\Models\RegDepartmentEchelon1;
use App\Models\RegProvince;
use App\Models\RegRegency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use PHPUnit\Metadata\Group;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->heading('Identitas Pribadi')
                    ->description('Nama, Alamat, Gender')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema(static::getClientIdentityForm())
                            ->columnSpan(5),
                    ])->columnSpan(['lg' => fn(?Client $record) => $record === null ? 3 : 2]),
                Forms\Components\Section::make()
                    ->heading('Pendidikan Terakhir')
                    ->description('Pendidikan Terakhir')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema(static::getClientEducationForm())
                            ->columnSpan(5),
                    ])->columnSpan(['lg' => fn(?Client $record) => $record === null ? 3 : 2]),
                Forms\Components\Section::make()
                    ->heading('Informasi Kepegawaian')
                    ->description('NIP, Jabatan, Jenjang')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema(static::getClientBasicInformationForm())
                            ->columnSpan(5),
                    ])->columnSpan(['lg' => fn(?Client $record) => $record === null ? 3 : 2]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->toggleable()
                    ->toggledHiddenByDefault()
                    ->label('id'),
                Tables\Columns\TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable(),
                Tables\Columns\TextColumn::make('identity.name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('crole.role_name')
                    ->label('Jabatan')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('croleLevel.level')
                    ->label('Jenjang')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Kluster ASN')
                    ->searchable(),
                Tables\Columns\TextColumn::make('agenciable.name')
                    ->label('Instansi')
                    ->searchable()->sortable(),
                Tables\Columns\TextColumn::make('echelonable.name')
                    ->label('Unit Kerja')
                    ->searchable()->sortable(),
                Tables\Columns\TextColumn::make('echelon_x_text')
                    ->label('Unit Kerja - Typed')
                    ->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('assignation_type')
                    ->label('Pengangkatan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
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
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'view' => Pages\ViewClient::route('/{record}'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): string
    {
        return __('labels.nav.client_management');
    }

    public static function getClientBasicInformationForm(): array
    {
        return [
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->searchable()
                        ->required()
                        ->relationship('user', 'name'),
                    Forms\Components\TextInput::make('nip')
                        ->label('NIP')
                        ->required()
                        ->maxLength(255),
                ])->columns(2),
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Select::make('c_role_id')
                        ->label(__('labels.form.crole.name'))
                        ->live()
                        ->relationship('crole', 'role_name')
                        ->required(),
                    Forms\Components\Select::make('c_role_level_id')
                        ->label(__('labels.form.crole.level'))
                        ->relationship('croleLevel', 'level', fn(Builder $query, Forms\Get $get) => $query->where('c_role_id', $get('c_role_id') == "" ? 0 : $get('c_role_id')))
                        ->required()
                ])->columns(2),
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label(__('labels.form.client_cluster'))
                        ->options(ClientCluster::class)
                        ->afterStateUpdated(function (?string $state, Forms\Set $set) {
                            if ($state == "central") {
                                $set("echelon_type", RegDepartmentEchelon1::class);
                            }
                        })
                        ->live()
                        ->required(),
                    Forms\Components\ToggleButtons::make('status')
                        ->label('Status')
                        ->options(ClientStatus::class)
                        ->inline()
                        ->required(),
                ])->columns(2),
            Forms\Components\Select::make('assignation_type')
                ->options(CRoleAssignation::class)
                ->label('Jenis Pengangkatan')
                ->required(),
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Select::make('agency_id')
                        ->label('Instansi')
                        ->live()
                        ->searchable()
                        ->required(fn(Forms\Get $get) => $get('type') == 'local_province')
                        ->hidden(fn(Forms\Get $get): bool => $get('type') != 'local_province')
                        ->options(RegProvince::query()->pluck('name', 'id')),
                    Forms\Components\Select::make('agency_id')
                        ->label('Instansi')
                        ->live()
                        ->searchable()
                        ->required(fn(Forms\Get $get) => $get('type') == 'local_regency')
                        ->hidden(fn(Forms\Get $get) => $get('type') != 'local_regency')
                        ->options(RegRegency::query()->pluck('name', 'id')),
                    Forms\Components\Select::make('agency_id')
                        ->label('Instansi')
                        ->live()
                        ->searchable()
                        ->required(fn(Forms\Get $get) => $get('type') == 'central')
                        ->hidden(fn(Forms\Get $get) => $get('type') != 'central')
                        ->options(RegDepartment::query()->pluck('name', 'id')),
                    Forms\Components\TextInput::make('echelon_x_text')
                        ->label('Unit Kerja')
                        ->required(fn(Forms\Get $get) => $get('type') != 'central')
                        ->hidden(fn(Forms\Get $get) => $get('type') == 'central'),
                    Forms\Components\Select::make('echelon_id')
                        ->label('Unit Kerja')
                        ->options(fn(Forms\Get $get) => RegDepartmentEchelon1::query()->where('department_id', $get('agency_id'))->pluck('name', 'id'))
                        ->required(fn(Forms\Get $get) => $get('type') == 'central'),
                ])->columns(2),
        ];
    }

    public static function getClientIdentityForm(): array
    {
        return [
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Nama')
                                ->required(),
                            Forms\Components\TextInput::make('academic_title')
                                ->label('Gelar Akademik')
                                ->hint('e.g: S.H, M.H')
                                ->required(),
                        ])
                        ->columns(2),
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\ToggleButtons::make('gender')
                                ->label('Jenis Kelamin')
                                ->options(Gender::class)
                                ->inline()
                                ->required(),
                            Forms\Components\TextInput::make('phone_number')
                                ->label('Nomor Telepon')
                                ->required(),
                        ])->columns(2),
                    Forms\Components\Textarea::make('address')
                        ->label('Alamat')
                        ->required(),
                    Forms\Components\FileUpload::make('photo')
                        ->label('Pas Foto')
                        ->maxSize(config('fungsional-pro.max_media_file_size'))
                        ->acceptedFileTypes(config('fungsional-pro.accepted_media_type'))
                        ->directory('photos')
                        ->image()
                        ->downloadable()
                        ->required()
                ])
                ->relationship('identity')
        ];
    }

    public static function getClientEducationForm(): array
    {
        return [
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\TextInput::make('university_name')
                                ->label('Universitas')
                                ->required(),
                            Forms\Components\TextInput::make('program_name')
                                ->label('Jurusan')
                                ->required(),
                        ])
                        ->columns(2),
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\TextInput::make('gpa')
                                ->label('IPK')
                                ->numeric()
                                ->required(),
                            Forms\Components\FileUpload::make('certificate')
                                ->label('Ijazah/Transkrip')
                                ->required()
                                ->maxFiles(1)
                                ->acceptedFileTypes(config('fungsional-pro.accepted_document_type'))
                                ->maxSize(config('fungsional-pro.max_upload_file_size'))
                                ->directory('education_certificate')
                                ->downloadable()
                        ])->columns(2)
                ])
                ->relationship('education')
        ];
    }

    public static function getDetailedClientForm(): array
    {
        return [

        ];
    }


    public static function getGloballySearchableAttributes(): array
    {
        return ['nip', 'identity.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Nama' => $record->identity->name,
            'NIP' => $record->nip
        ];
    }
}
