<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientPositionResource\Pages;
use App\Filament\Resources\ClientPositionResource\RelationManagers;
use App\Models\ClientPosition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Enums\CRoleAssignation;
use App\Models\CRoleLevel;
use App\Models\CRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction;


class ClientPositionResource extends Resource
{
    protected static ?string $model = ClientPosition::class;


    protected static ?string $navigationLabel = 'Riwayat Jabatan';

    protected static ?string $modelLabel = 'Riwayat Jabatan';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'croleLevel.role',
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('c_role_id')
                    ->label(__('labels.form.client.fields.crole_name'))
                    ->options(
                        CRole::query()
                            ->where('active', true)
                            ->pluck('role_name', 'id')
                    )
                    ->live()
                    ->dehydrated(false)
                    ->afterStateHydrated(function (callable $set, $record) {
                        if ($record?->croleLevel?->c_role_id) {
                            $set('c_role_id', $record->croleLevel->c_role_id);
                        }
                    })
                    ->afterStateUpdated(fn (callable $set) => $set('c_role_level_id', null))
                    ->required(),

                Forms\Components\Select::make('c_role_level_id')
                    ->label(__('labels.form.client.fields.crole_grade'))
                    ->options(fn (Forms\Get $get) =>
                        CRoleLevel::query()
                            ->where('c_role_id', $get('c_role_id'))
                            ->pluck('level', 'id')
                    )
                    ->disabled(fn (Forms\Get $get) => blank($get('c_role_id')))
                    ->required(),

                Forms\Components\Select::make('type')
                    ->options(CRoleAssignation::class)
                    ->label(__('labels.form.client.fields.assignation_type'))
                    ->required(),
                Forms\Components\DatePicker::make('effective_date')
                     ->label('TMT Jabatan')
                    ->required(),
                Forms\Components\TextInput::make('decree_number')
                    ->label('Nomor SK Jabatan')
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('decree_file')
                            ->disk('s3')
                            ->label('File SK Jabatan')
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
                Tables\Columns\TextColumn::make('croleLevel.role.role_name')
                    ->label('Jabatan')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('croleLevel.level')
                    ->label('Jenjang')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Jenis Pengangkatan')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'current' => 'Aktif',
                        'past' => 'Riwayat',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state) => match ($state) {
                        'current' => 'success',
                        'past' => 'gray',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('effective_date')
                    ->label('TMT Jabatan')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('decree_number')
                    ->label('Nomor SK')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                MediaAction::make()
                    ->media(fn(Model $record) => Storage::temporaryUrl($record->decree_file, now()->addMinutes(10)))
                    ->label('SK Jabatan'),
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
            'index' => Pages\ListClientPositions::route('/'),
            'create' => Pages\CreateClientPosition::route('/create'),
            'view' => Pages\ViewClientPosition::route('/{record}'),
            'edit' => Pages\EditClientPosition::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): string
    {
        return __('labels.nav.client_menu');
    }

}
