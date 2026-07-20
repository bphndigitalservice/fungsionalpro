<?php

namespace App\Filament\Resources\ClientPositions;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ClientPositions\Pages\ListClientPositions;
use App\Filament\Resources\ClientPositions\Pages\CreateClientPosition;
use App\Filament\Resources\ClientPositions\Pages\ViewClientPosition;
use App\Filament\Resources\ClientPositions\Pages\EditClientPosition;
use App\Filament\Resources\ClientPositions\Pages;
use App\Filament\Resources\ClientPositions\RelationManagers;
use App\Models\ClientPosition;
use Filament\Forms;
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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('c_role_id')
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

                Select::make('c_role_level_id')
                    ->label(__('labels.form.client.fields.crole_grade'))
                    ->options(fn (Get $get) =>
                        CRoleLevel::query()
                            ->where('c_role_id', $get('c_role_id'))
                            ->pluck('level', 'id')
                    )
                    ->disabled(fn (Get $get) => blank($get('c_role_id')))
                    ->required(),

                Select::make('type')
                    ->options(CRoleAssignation::class)
                    ->label(__('labels.form.client.fields.assignation_type'))
                    ->required(),
                DatePicker::make('effective_date')
                     ->label('TMT Jabatan')
                    ->required(),
                TextInput::make('decree_number')
                    ->label('Nomor SK Jabatan')
                    ->required()
                    ->maxLength(255),
                FileUpload::make('decree_file')
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
                TextColumn::make('croleLevel.role.role_name')
                    ->label('Jabatan')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('croleLevel.level')
                    ->label('Jenjang')
                    ->sortable(),

                TextColumn::make('type')
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

                TextColumn::make('effective_date')
                    ->label('TMT Jabatan')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('decree_number')
                    ->label('Nomor SK')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                MediaAction::make()
                    ->media(fn(Model $record) => Storage::temporaryUrl($record->decree_file, now()->addMinutes(10)))
                    ->label('SK Jabatan'),
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
            'index' => ListClientPositions::route('/'),
            'create' => CreateClientPosition::route('/create'),
            'view' => ViewClientPosition::route('/{record}'),
            'edit' => EditClientPosition::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): string
    {
        return __('labels.nav.client_menu');
    }

}
