<?php

namespace App\Filament\Resources;

use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Enums\JenisKepegawaian;
use App\Filament\Resources\MasterJfResource\Pages;
use App\Models\CRole;
use App\Models\MasterJf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MasterJfImport;
use Illuminate\Support\Facades\Storage;

class MasterJfResource extends Resource
{
    protected static ?string $model = MasterJf::class;
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama')
                    ->required(),
                Forms\Components\TextInput::make('nip')
                    ->label('NIP')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('gol_ruang')
                    ->label('Golongan/Ruang')
                    ->options(
                        \App\Models\RegGrade::query()
                            ->get()
                            ->mapWithKeys(fn ($grade) => [
                                "{$grade->grade_name} ({$grade->grade_code})"
                                    => "{$grade->grade_name} ({$grade->grade_code})"
                            ])
                            ->toArray()
                    )
                    ->searchable(),
                Forms\Components\TextInput::make('jabatan'),
                Forms\Components\Select::make('c_role_id')
                    ->label('Jabatan Fungsional')
                    ->options(fn (): array => CRole::query()
                        ->where('active', true)
                        ->orderBy('role_name')
                        ->pluck('role_name', 'id')
                        ->all())
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('instansi'),
                Forms\Components\Select::make('type')
                    ->label('Kluster')
                    ->options(ClientCluster::class)
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('unit_kerja')
                    ->label('Unit Kerja'),
                Forms\Components\Select::make('pengangkatan')
                    ->label('Pengangkatan')
                    ->options(MasterJf::pengangkatanOptions())
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(ClientStatus::class)
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('status_kepegawaian')
                    ->label('Status Kepegawaian')
                    ->options(JenisKepegawaian::class)
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gol_ruang')
                    ->label('Golongan/Ruang')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jabatan')
                    ->label('Jabatan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cRole.role_name')
                    ->label('Jabatan Fungsional')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_kerja')
                    ->label('Unit Kerja')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('instansi')
                    ->label('Instansi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Kluster')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pengangkatan')
                    ->label('Pengangkatan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_kepegawaian')
                    ->label('Status Kepegawaian')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(ClientStatus::class),
                Tables\Filters\SelectFilter::make('status_kepegawaian')
                    ->label('Status Kepegawaian')
                    ->options(JenisKepegawaian::class),
                Tables\Filters\SelectFilter::make('pengangkatan')
                    ->options(fn (): array => MasterJf::pengangkatanOptions()),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Kluster')
                    ->options(ClientCluster::class)
                    ->searchable(),
                Tables\Filters\SelectFilter::make('gol_ruang')
                    ->label('Golongan/Ruang')
                    ->options(fn (): array => MasterJf::distinctOptions('gol_ruang'))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('instansi')
                    ->options(fn (): array => MasterJf::distinctOptions('instansi'))
                    ->searchable()
                    ->multiple(),
                Tables\Filters\SelectFilter::make('unit_kerja')
                    ->label('Unit Kerja')
                    ->options(fn (): array => MasterJf::distinctOptions('unit_kerja'))
                    ->searchable()
                    ->multiple(),
                Tables\Filters\SelectFilter::make('jabatan')
                    ->options(fn (): array => MasterJf::distinctOptions('jabatan'))
                    ->searchable()
                    ->multiple(),
                Tables\Filters\SelectFilter::make('c_role_id')
                    ->label('Jabatan Fungsional')
                    ->options(fn (): array => CRole::query()
                        ->where('active', true)
                        ->orderBy('role_name')
                        ->pluck('role_name', 'id')
                        ->all())
                    ->searchable(),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->headerActions([
                Action::make('import')
                    ->label('Import Data')
                    ->form([
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
                        } catch (\Exception $e) {
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
            ->actions([
                // Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMasterJfs::route('/'),
            'edit' => Pages\EditMasterJf::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): string
    {
        return __('labels.nav.client_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('Master Data JF');
    }

    public static function getModelLabel(): string
    {
        return __('Master Data JF');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Master Data JF');
    }
}
