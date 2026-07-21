<?php

namespace App\Filament\Resources;

use App\Concerns\Filament\ChecksPhotoUpload;
use App\Filament\Exports\ClientActivityExporter;
use App\Filament\Resources\ClientActivityResource\Pages;
use App\Filament\Resources\ClientActivityResource\RelationManagers;
use App\Models\Client;
use App\Models\ClientActivity;
use App\Models\RegProvince;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Textarea;
use Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\TimePicker;
use Filament\Tables\Actions\ExportAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ClientActivityResource extends Resource
{
    use ChecksPhotoUpload;

    protected static ?string $model = ClientActivity::class;

    protected static ?string $navigationLabel = 'Riwayat Kegiatan';

    protected static ?string $modelLabel = 'Riwayat Kegiatan';

    /**
     * Master array helper to manage types centrally
     */
    public static function getJenisKegiatanOptions(): array
    {
        return [
            1 => 'Ceramah Langsung',
            2 => 'Ceramah Daring/Online',
            3 => 'Konsultasi Langsung',
            4 => 'Konsultasi Daring/Online',
            5 => 'Penyuluhan Hukum Keliling',
            6 => 'Podcast/Dialog Interaktif',
            7 => 'Pameran',
            8 => 'Film Penyuluhan Hukum',
            9 => 'Talkshow Radio/TV',
            10 => 'MedSos/Media Digital Lainnya',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema(
            static::getFormSchema()
        );
    }

    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('jenis_kegiatan')
                                ->label('Jenis Kegiatan')
                                ->options(static::getJenisKegiatanOptions())
                                ->live()
                                ->visible(fn () => Client::current()?->c_role_id == 2)
                                ->required(fn () => Client::current()?->c_role_id == 2),

                            Select::make('reg_province_id')
                                ->label('Provinsi Pelaksanaan Kegiatan')
                                ->options(fn () => once(fn () => RegProvince::query()->pluck('name', 'id')->all()))
                                ->searchable()
                                ->visible(fn () => Client::current()?->c_role_id == 2)
                                ->required(fn () => Client::current()?->c_role_id == 2),
                        ]),

                    TextInput::make('title')
                        ->label('Nama Kegiatan')
                        ->columnSpanFull()
                        ->required(),

                    Forms\Components\Fieldset::make()
                        ->label('Waktu Pelaksanaan Kegiatan')
                        ->schema([
                            DatePicker::make('start_period')
                                ->minDate('2020-01-01')
                                ->label(fn () =>
                                    Client::current()?->c_role_id == 1
                                        ? 'Tanggal Mulai'
                                        : 'Tanggal'
                                )
                                ->required(),

                            DatePicker::make('end_period')
                                ->minDate('2020-01-01')
                                ->label('Selesai')
                                ->visible(fn () => Client::current()?->c_role_id == 1)
                                ->required(fn () => Client::current()?->c_role_id == 1),

                            Forms\Components\Grid::make(2)
                                ->visible(fn () => Client::current()?->c_role_id == 2)
                                ->schema([
                                    TimePicker::make('start_time')
                                        ->label('Jam Mulai')
                                        ->seconds(false)
                                        ->required(),

                                    TimePicker::make('end_time')
                                        ->label('Jam Selesai')
                                        ->seconds(false)
                                        ->required(),
                                ]),
                        ])
                        ->columns(2),

                    Forms\Components\Section::make('Detail Kegiatan')
                        ->schema([
                            TextInput::make('activity_details.lokasi')
                                ->label('Lokasi / Tempat Kegiatan')
                                ->required(),

                            TextInput::make('activity_details.jumlah_peserta')
                                ->numeric()
                                ->label('Jumlah Peserta')
                                ->required(),

                            TextInput::make('activity_details.penerima')
                                ->label('Penerima')
                                ->required(),

                            Textarea::make('activity_details.materi')
                                ->label(fn (Get $get) => in_array((int)$get('jenis_kegiatan'), [3, 4]) ? 'Jenis Kasus' : 'Materi')
                                ->required(),
                        ])
                        ->visible(fn () => Client::current()?->c_role_id == 2),

                    Textarea::make('description')
                        ->label('Deskripsi Kegiatan')
                        ->rows(5)
                        ->required()
                        ->helperText(function (Get $get) {
                            $jenisKegiatan = (int) $get('jenis_kegiatan');
                            if (in_array($jenisKegiatan, [6, 8, 10])) {
                                return 'Catatan: Untuk jenis kegiatan ini, mohon cantumkan atau lampirkan tautan (link) media pada kolom deskripsi.';
                            }
                            return null;
                        }),

                    Forms\Components\FileUpload::make('activity_file')
                        ->disk('s3')
                        ->label('Lampiran Laporan Kegiatan')
                        ->helperText(function () {
                            $size = config('fungsional-pro.max_upload_file_size');
                            $baseText = "Format file: PDF | Maksimal ukuran: {$size} KB.";

                            if (Client::current()?->c_role_id == 2) {
                                return $baseText . " Lampiran terdiri dari Laporan Kegiatan, Dokumentasi Kegiatan, Surat Perintah, dan Evaluasi (jika ada).";
                            }

                            return $baseText;
                        })
                        ->required()
                        ->maxFiles(1)
                        ->acceptedFileTypes(config('fungsional-pro.accepted_document_type'))
                        ->maxSize(config('fungsional-pro.max_upload_file_size'))
                        ->directory('activity_file')
                        ->visibility('private')
                        ->downloadable(),
                ])
        ];
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

                TextColumn::make('jenis_kegiatan')
                    ->label('Jenis Kegiatan')
                    ->searchable()
                    ->sortable()
                    ->visible(fn () => Client::current()?->c_role_id == 2)
                    ->formatStateUsing(fn ($state) => static::getJenisKegiatanOptions()[(int)$state] ?? '-'),

                TextColumn::make('regProvince.name')
                    ->label('Provinsi')
                    ->searchable()
                    ->sortable()
                    ->visible(fn () => Client::current()?->c_role_id == 2)
                    ->placeholder('-'),

                TextColumn::make('start_period')
                    ->label(fn () =>
                        Client::current()?->c_role_id == 1
                            ? 'Tanggal Mulai'
                            : 'Tanggal')
                    ->date()
                    ->sortable(),

                TextColumn::make('end_period')
                    ->label('Tanggal Selesai')
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
                    ->limit(100)
                    ->tooltip(fn ($record) => $record->activity_details['materi'] ?? null)
                    ->visible(fn () => Client::current()?->c_role_id == 2),

                TextColumn::make('description')
                    ->label('Deskripsi Kegiatan')
                    ->wrap()
                    ->limit(100)
                    ->tooltip(fn ($record) => $record->description),

                TextColumn::make('is_verified')
                    ->label('Status Verifikasi')
                    ->getStateUsing(function ($record) {
                        if (! $record) {
                            return '-';
                        }

                        if (is_null($record->is_verified)) {
                            return 'Sedang Diverifikasi';
                        }

                        return $record->is_verified
                            ? 'Terverifikasi'
                            : 'Ditolak';
                    })
                    ->badge()
                    ->color(function ($record) {
                        if (! $record) {
                            return 'gray';
                        }

                        if (is_null($record->is_verified)) {
                            return 'gray';
                        }

                        return $record->is_verified
                            ? 'success'
                            : 'danger';
                    })
                    ->tooltip(function ($record) {
                        if ($record?->is_verified === false) {
                            return "Alasan Penolakan: {$record->verification_note}";
                        } if ($record?->is_verified === true) {
                            return "Kegiatan telah diverifikasi";
                        } if (is_null($record?->is_verified)) {
                            return "Menunggu verifikasi";
                        }
                        return null;
                    }),
            ])
            ->filters([
                SelectFilter::make('jenis_kegiatan')
                    ->label('Jenis Kegiatan')
                    ->options(static::getJenisKegiatanOptions())
                    ->visible(fn () => Client::current()?->c_role_id == 2),

                Filter::make('tahun_kegiatan')
                    ->form([
                        Select::make('tahun')
                            ->label('Tahun')
                            ->options(
                                collect(range(date('Y'), 2020))
                                    ->mapWithKeys(fn ($year) => [$year => $year])
                                    ->toArray()
                            ),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['tahun'],
                            fn (Builder $query, $year): Builder => $query->whereBetween(
                                'start_period',
                                ["{$year}-01-01", "{$year}-12-31"]
                            )
                        );
                    })
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(ClientActivityExporter::class)
                    ->color('success')
                    ->button()
                    ->icon('heroicon-m-arrow-down-tray'),
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
}
