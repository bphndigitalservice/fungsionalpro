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
use Filament\Forms\Components\TimePicker;
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

                        // Tanggal untuk semua c_role_id (Analis Hukum dan Penyuluh Hukum)
                        DatePicker::make('start_period')
                            ->label('Tanggal')
                            ->required(),

                        // End date hanya c_role_id 1 (Analis Hukum)
                        DatePicker::make('end_period')
                            ->label('Selesai')
                            ->visible(fn () => Client::current()?->c_role_id == 1)
                            ->required(fn () => Client::current()?->c_role_id == 1),

                        // Jam hanya jika c_role_id 2 (Penyuluh Hukum)
                        TimePicker::make('start_time')
                            ->label('Jam Mulai')
                            ->seconds(false)
                            ->visible(fn () => Client::current()?->c_role_id == 2)
                            ->required(fn () => Client::current()?->c_role_id == 2),

                        TimePicker::make('end_time')
                            ->label('Jam Selesai')
                            ->seconds(false)
                            ->visible(fn () => Client::current()?->c_role_id == 2)
                            ->required(fn () => Client::current()?->c_role_id == 2),

                    ])
                    ->columns(2),
                        Forms\Components\Section::make('Detail Kegiatan')
                            ->schema([
                                TextInput::make('activity_details.lokasi')
                                    ->label('Lokasi / Tempat Kegiatan'),

                                TextInput::make('activity_details.jumlah_peserta')
                                    ->numeric()
                                    ->label('Jumlah Peserta'),

                                TextInput::make('activity_details.penerima')
                                    ->label('Penerima'),

                                Textarea::make('activity_details.materi')
                                    ->label('Materi'),
                            ])
                            ->visible(fn () => Client::current()?->c_role_id == 2),

                        Textarea::make('description')
                            ->label('Deskripsi Kegiatan')
                            ->rows(5)
                            ->required(),
                        Forms\Components\FileUpload::make('activity_file')
                            ->disk('s3')
                            ->label('Lampiran Laporan Kegiatan')
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
                    ->wrap()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('start_period')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),

                TextColumn::make('end_period')
                    ->label('Selesai')
                    ->date()
                    ->visible(fn () => Client::current()?->c_role_id == 1),

                TextColumn::make('jam')
                    ->label('Jam')
                    ->getStateUsing(fn ($record) =>
                        $record->start_time . ' - ' . $record->end_time
                    )
                    ->visible(fn () => Client::current()?->c_role_id == 2),

                    TextColumn::make('activity_details.lokasi')
                        ->label('Lokasi')
                        ->visible(fn () => Client::current()?->c_role_id == 2)
                        ->wrap(),

                    TextColumn::make('activity_details.jumlah_peserta')
                        ->label('Peserta')
                        ->visible(fn () => Client::current()?->c_role_id == 2),

                    TextColumn::make('activity_details.penerima')
                        ->label('Penerima')
                        ->visible(fn () => Client::current()?->c_role_id == 2)
                        ->wrap(),

                    TextColumn::make('activity_details.materi')
                        ->label('Materi')
                        ->wrap()
                        ->visible(fn () => Client::current()?->c_role_id == 2),

                TextColumn::make('description')
                    ->label('Deskripsi Kegiatan')
                    ->wrap(),

            ])

            ->actions([
                MediaAction::make()
                    ->media(fn(Model $record) => Storage::temporaryUrl($record->activity_file, now()->addMinutes(10)))
                    ->label('Lampiran Laporan Kegiatan'),
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
