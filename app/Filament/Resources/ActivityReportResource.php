<?php

namespace App\Filament\Resources;

// 1. Import your newly created exporter class
use App\Filament\Exports\ActivityReportExporter;
use App\Enums\SystemRole;
use App\Filament\Resources\ActivityReportResource\Pages;
use App\Models\ClientActivity;
use App\Models\AdminAccess;
use App\Models\Client;
use App\Models\RegProvince;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
// 2. Import the required export actions
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ExportBulkAction;
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
    public static function form(Form $form): Form
    {
        return $form->schema(static::getFormSchema());
    }

    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make()
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Nama Kegiatan')
                        ->columnSpanFull()
                        ->required(),
                    Forms\Components\Fieldset::make()
                        ->label('Waktu Pelaksanaan Kegiatan')
                        ->schema([
                            Forms\Components\DatePicker::make('start_period')->minDate('2020-01-01')->required(),
                            Forms\Components\DatePicker::make('end_period')->minDate('2020-01-01'),
                        ])->columns(2),
                    Forms\Components\Textarea::make('description')
                        ->label('Deskripsi Kegiatan')
                        ->rows(5)
                        ->required(),
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
                    ->formatStateUsing(fn ($state) => static::getJenisKegiatanOptions()[(int)$state] ?? '-'),

                TextColumn::make('reg_province_id')
                    ->label('Provinsi')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        if (! $state) return '-';
                        return RegProvince::find($state)?->name ?? '-';
                    }),

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
                    ->options(static::getJenisKegiatanOptions()),

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
                            fn (Builder $query, $year): Builder => $query->whereYear('start_period', $year)
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
            ->actions([
                Tables\Actions\ViewAction::make()->label('Lihat Detail')->modalHeading('Detail Kegiatan'),
                MediaAction::make()
                    ->media(fn(Model $record) => Storage::temporaryUrl($record->activity_file, now()->addMinutes(10)))
                    ->label('Lampiran Laporan Kegiatan'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(ActivityReportExporter::class)
                        ->label('Ekspor Baris Terpilih'),
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListActivityReports::route('/'),
            'create' => Pages\CreateActivityReport::route('/create'),
            'edit' => Pages\EditActivityReport::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $allowedRoleIds = AdminAccess::where('user_id', $user->id)->pluck('c_role_id')->toArray();

        return parent::getEloquentQuery()
            ->whereHas('client', function (Builder $query) use ($allowedRoleIds) {
                $query->whereIn('c_role_id', $allowedRoleIds);
            });
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
