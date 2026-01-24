<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientActivityResource\Pages;
use App\Filament\Resources\ClientActivityResource\RelationManagers;
use App\Models\Client;
use App\Models\ClientActivity;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Textarea;
use Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ClientActivityResource extends Resource
{
    protected static ?string $model = ClientActivity::class;

    protected static ?string $navigationLabel = 'Riwayat Kegiatan';

    protected static ?string $modelLabel = 'Riwayat Kegiatan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        TextInput::make('title')
                            ->label('Nama Kegiatan')
                            ->columnSpanFull()
                            ->required(),
                        Forms\Components\Fieldset::make()
                            ->label('Waktu Pelaksanaan Kegiatan')
                            ->schema([
                                DatePicker::make('start_period')
                                    ->label('Mulai')
                                    ->required(),
                                DatePicker::make('end_period')
                                    ->label('Selesai')
                                    ->required(),
                            ])->columns(2),
                        Textarea::make('description')
                            ->label('Deskripsi Kegiatan')
                            ->rows(5)
                            ->required(),
                        Forms\Components\FileUpload::make('activity_file')
                            ->disk('s3')
                            ->label('Lampiran Bukti Kegiatan')
                            ->required()
                            ->maxFiles(1)
                            ->acceptedFileTypes(config('fungsional-pro.accepted_document_type'))
                            ->maxSize(config('fungsional-pro.max_upload_file_size'))
                            ->directory('activity_file')
                            ->visibility('private')
                            ->downloadable(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Nama Kegiatan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('start_period')
                    ->label('Mulai Kegiatan')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_period')
                    ->label('Selesai Kegiatan')
                    ->date()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Deskripsi Kegiatan')
                    ->sortable()
                    ->wrap(),
            ])
            ->actions([
                MediaAction::make()
                    ->media(fn(Model $record) => Storage::temporaryUrl($record->activity_file, now()->addMinutes(10)))
                    ->label('Lampiran Bukti Kegiatan'),
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


    public static function getNavigationGroup(): string
    {
        return __('labels.nav.client_menu');
    }

    public static function getRoutePath(): string
    {
        return '/c/activities';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientActivities::route('/'),
            'create' => Pages\CreateClientActivity::route('/create'),
            'edit' => Pages\EditClientActivity::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Client::current() !== null;
    }
}
