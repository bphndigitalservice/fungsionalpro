<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Models\Client;
use App\Models\Enums\ClientCluster;
use App\Models\Enums\ClientStatus;
use App\Models\Enums\CRoleAssignation;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use PHPUnit\Metadata\Group;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->heading('Informasi Dasar')
                            ->description('NIP, Jabatan, Jenjang')
                            ->collapsible()
                            ->schema([
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
                                Forms\Components\Select::make('agency_id')
                                    ->label('Provinsi')
                                    ->live()
                                    ->searchable()
                                    ->required(fn(Forms\Get $get) => $get('type') == 'local_province')
                                    ->hidden(fn(Forms\Get $get): bool => $get('type') != 'local_province')
                                    ->options(RegProvince::query()->pluck('name', 'id')),
                                Forms\Components\Select::make('agency_id')
                                    ->label('Kota/Kabupaten')
                                    ->live()
                                    ->searchable()
                                    ->required(fn(Forms\Get $get) => $get('type') == 'local_regency')
                                    ->hidden(fn(Forms\Get $get) => $get('type') != 'local_regency')
                                    ->options(RegRegency::query()->pluck('name', 'id')),
                                Forms\Components\Select::make('agency_id')
                                    ->label('Kementerian/Lembaga')
                                    ->live()
                                    ->searchable()
                                    ->required(fn(Forms\Get $get) => $get('type') == 'central')
                                    ->hidden(fn(Forms\Get $get) => $get('type') != 'central')
                                    ->options(RegDepartment::query()->pluck('name', 'id')),
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('echelon_type')
                                            ->required(fn(Forms\Get $get) => $get('type') == 'central')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('echelon_id')
                                            ->required(fn(Forms\Get $get) => $get('type') == 'central' )
                                            ->numeric(),
                                    ])
                                    ->hidden(fn(Forms\Get $get) => $get('type') != 'central' || $get('type') == "")
                                    ->columns(2)
                            ])->columnSpan(['lg' => fn(?Client $record) => $record === null ? 3 : 2]),
                    ])
                    ->columnSpan(5),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID'),
                Tables\Columns\TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('c_role_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nip')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('agency_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('agency_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('echelon_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('echelon_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('assignation_type')
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
}
