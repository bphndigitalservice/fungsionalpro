<?php

namespace App\Filament\Resources;

// 1. Import your newly created exporter class
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Actions\ExportAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportBulkAction;
use App\Filament\Resources\ActivityReportResource\Pages\ListActivityReports;
use App\Services\ClientAccessService;
use App\Filament\Exports\ActivityReportExporter;
use App\Enums\SystemRole;
use App\Filament\Resources\ActivityReportResource\Pages;
use App\Models\ClientActivity;
use App\Models\Client;
use App\Models\RegProvince;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction;

class ActivityReportResource extends Resource
{
    protected static ?string $model = ClientActivity::class;
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Pelaporan Kegiatan';
    protected static ?string $modelLabel = 'Pelaporan Kegiatan';

    public static function getNavigationGroup(): string
    {
        return __('labels.nav.client_management');
    }

    // Keep form schemas exactly as they are...

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
    public static function form(Schema $schema): Schema
    {
        return $schema->components(static::getFormSchema());
    }

    public static function getFormSchema(): array
    {
        return [
            Section::make()
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

                    Fieldset::make()
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

                            Grid::make(2)
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

                    Section::make('Detail Kegiatan')
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
                        ->required(),

                    FileUpload::make('activity_file')
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
                TextColumn::make('client.identity.name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('client.nip')
                    ->label('NIP')
                    ->searchable()
                    ->sortable(),

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
                    ->schema([
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
                ExportAction::make('export_all')
                    ->label('Ekspor Pelaporan Kegiatan')
                    ->exporter(ActivityReportExporter::class)
                    ->modifyQueryUsing(fn (Builder $query) => static::getEloquentQuery())
                    ->color('success')
                    ->button()
                    ->icon('heroicon-m-arrow-down-tray'),
            ])
            ->recordActions([
                ViewAction::make()->label('Lihat Detail')->modalHeading('Detail Kegiatan'),
                MediaAction::make()
                    ->media(fn(Model $record) => Storage::temporaryUrl($record->activity_file, now()->addMinutes(10)))
                    ->label('Lampiran Laporan Kegiatan'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(ActivityReportExporter::class)
                        ->label('Ekspor Baris Terpilih'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityReports::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin()) {
            return parent::getEloquentQuery();
        }

        $scopedClientIds = app(ClientAccessService::class)
            ->scopedQuery($user)
            ->select('clients.id');

        return parent::getEloquentQuery()
            ->whereIn('client_id', $scopedClientIds);
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        return $user && ($user->hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin, SystemRole::AdminInstansi));
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && ($user->hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin, SystemRole::AdminInstansi));
    }
}
