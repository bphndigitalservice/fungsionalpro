<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use App\Models\RegGrade;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Exception;
use App\Filament\Resources\MasterJfResource\Pages\ListMasterJfs;
use App\Filament\Resources\MasterJfResource\Pages\EditMasterJf;
use App\Filament\Resources\MasterJfResource\Pages;
use App\Models\MasterJf;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MasterJfImport;
use Illuminate\Support\Facades\Storage;

class MasterJfResource extends Resource
{
    protected static ?string $model = MasterJf::class;
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->required(),
                TextInput::make('nip')
                    ->label('NIP')
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('gol_ruang')
                    ->label('Golongan/Ruang')
                    ->options(
                        RegGrade::query()
                            ->get()
                            ->mapWithKeys(fn ($grade) => [
                                "{$grade->grade_name} ({$grade->grade_code})"
                                    => "{$grade->grade_name} ({$grade->grade_code})"
                            ])
                            ->toArray()
                    )
                    ->searchable(),
                TextInput::make('jabatan'),
                TextInput::make('instansi'),
                TextInput::make('type')
                    ->label('Tipe'),
                TextInput::make('unit_kerja')
                    ->label('Unit Kerja'),
                Select::make('pengangkatan')
                    ->label('Pengangkatan')
                    ->options([
                        'CPNS/PPPK' => 'CPNS/PPPK',
                        'Inpassing' => 'Inpassing',
                        'PDJL' => 'PDJL',
                        'Penyetaraan' => 'Penyetaraan',
                    ])
                    ->searchable()
                    ->preload(),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'Aktif' => 'Aktif',
                        'Mengundurkan diri' => 'Mengundurkan diri',
                        'Diberhentikan Sementara sebagai PNS'
                            => 'Diberhentikan Sementara sebagai PNS',
                        'CTLN' => 'CTLN',
                        'Tugas belajar > 6 Bulan'
                            => 'Tugas belajar > 6 Bulan',
                        'Ditugaskan secara penuh di luar jabatan'
                            => 'Ditugaskan secara penuh di luar jabatan',
                        'Tidak Memenuhi Persyaratan Jabatan'
                            => 'Tidak Memenuhi Persyaratan Jabatan',
                    ])
                    ->searchable()
                    ->preload(),
                Select::make('status_kepegawaian')
                    ->label('Status Kepegawaian')
                    ->options([
                        'PNS' => 'PNS',
                        'PPPK' => 'PPPK',
                    ])
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->searchable(),
                TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable(),
                TextColumn::make('gol_ruang')
                    ->label('Golongan/Ruang')
                    ->searchable(),
                TextColumn::make('jabatan')
                    ->label('Jabatan')
                    ->searchable(),
                TextColumn::make('unit_kerja')
                    ->label('Unit Kerja')
                    ->searchable(),
                TextColumn::make('instansi')
                    ->label('Instansi')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->searchable(),
                TextColumn::make('pengangkatan')
                    ->label('Pengangkatan')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status'),
                TextColumn::make('status_kepegawaian')
                    ->label('Status Kepegawaian')
                    ->searchable(),
            ])
            ->headerActions([
                Action::make('import')
                    ->label('Import Data')
                    ->schema([
                        FileUpload::make('file')
                            ->label('File Excel')
                            ->disk('local')
                            ->directory('imports/master-jf')
                            ->visibility('private')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                            ])
                            ->maxSize(5120)
                            ->helperText('Format file: .xlsx atau .xls. Maksimal ukuran file 5 MB.')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $relativePath = $data['file'] ?? null;

                        if (
                            ! is_string($relativePath)
                            || $relativePath === ''
                            || str_contains($relativePath, '..')
                            || str_starts_with($relativePath, '/')
                            || ! str_starts_with($relativePath, 'imports/master-jf/')
                        ) {
                            Notification::make()
                                ->title('Import gagal')
                                ->body('File import tidak valid.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $disk = Storage::disk('local');

                        if (! $disk->exists($relativePath)) {
                            Notification::make()
                                ->title('Import gagal')
                                ->body('File import tidak ditemukan. Silakan upload ulang.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $basePath = realpath($disk->path('imports/master-jf'));
                        $filePath = realpath($disk->path($relativePath));

                        if ($basePath === false || $filePath === false || ! str_starts_with($filePath, $basePath.DIRECTORY_SEPARATOR)) {
                            Notification::make()
                                ->title('Import gagal')
                                ->body('File import tidak valid.')
                                ->danger()
                                ->send();

                            return;
                        }

                        try {
                            set_time_limit(300);

                            Excel::import(new MasterJfImport, $filePath);

                            Notification::make()
                                ->title('Import berhasil')
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            report($e);

                            Notification::make()
                                ->title('Import gagal')
                                ->body('Terjadi kendala saat membaca file. Silakan upload ulang.')
                                ->danger()
                                ->persistent()
                                ->send();
                        } finally {
                            $disk->delete($relativePath);
                        }
                    }),
            ])
            ->recordActions([
                // Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMasterJfs::route('/'),
            'edit' => EditMasterJf::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): string
    {
        return __('labels.nav.client_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('Import Data Klien');
    }

    public static function getModelLabel(): string
    {
        return __('Import Data Klien');
    }
}
