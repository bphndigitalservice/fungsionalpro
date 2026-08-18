<?php

namespace App\Filament\Resources;

use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Enums\JenisKepegawaian;
use App\Filament\Resources\MasterJfResource\Pages;
use App\Imports\MasterJfImport;
use App\Models\CRole;
use App\Models\CRoleLevel;
use App\Models\MasterJf;
use App\Models\RegDepartment;
use App\Models\RegGrade;
use App\Models\RegProvince;
use App\Models\RegRegency;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class MasterJfResource extends Resource
{
    protected static ?string $model = MasterJf::class;

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()->with('agenciable');

        if ($user && $user->isSuperAdmin()) {
            return $query;
        }

        $allowedRoleIds = $user->adminAccesses()->pluck('c_role_id');

        return $query->whereIn('c_role_id', $allowedRoleIds);
    }

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
                Forms\Components\Select::make('reg_grade_id')
                    ->label('Golongan/Ruang')
                    ->relationship('grade', 'grade_code')
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('jabatan'),
                Forms\Components\Select::make('c_role_id')
                    ->label('Jabatan Fungsional')
                    ->options(fn (): array => CRole::query()
                        ->where('active', true)
                        ->orderBy('role_name')
                        ->pluck('role_name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('c_role_level_id', null)),
                Forms\Components\Select::make('c_role_level_id')
                    ->label('Jenjang')
                    ->options(fn (Forms\Get $get): array => CRoleLevel::query()
                        ->where('c_role_id', $get('c_role_id') ?: 0)
                        ->orderBy('level')
                        ->pluck('level', 'id')
                        ->all())
                    ->searchable()
                    ->disabled(fn (Forms\Get $get): bool => blank($get('c_role_id')))
                    ->dehydrated(true),
                Forms\Components\Select::make('type')
                    ->label('Kluster')
                    ->options(ClientCluster::class)
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('agency_id', null)),
                Forms\Components\Select::make('agency_id')
                    ->label('Instansi')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->options(function (Forms\Get $get): array {
                        $type = $get('type');
                        $value = $type instanceof ClientCluster ? $type->value : $type;

                        return match ($value) {
                            ClientCluster::Central->value => RegDepartment::query()->orderBy('name')->pluck('name', 'id')->all(),
                            ClientCluster::LocalProvince->value => RegProvince::query()->orderBy('name')->pluck('name', 'id')->all(),
                            ClientCluster::LocalRegency->value => RegRegency::query()->orderBy('name')->pluck('name', 'id')->all(),
                            default => [],
                        };
                    })
                    ->disabled(function (Forms\Get $get): bool {
                        $type = $get('type');
                        $value = $type instanceof ClientCluster ? $type->value : $type;

                        return blank($value);
                    })
                    ->dehydrated(true),
                Forms\Components\TextInput::make('unit_kerja')
                    ->label('Unit Kerja'),
                Forms\Components\TextInput::make('provinsi')
                    ->label('Provinsi'),
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
                Tables\Columns\TextColumn::make('grade.clean_name')
                    ->label('Golongan/Ruang')
                    ->searchable(['grade_code', 'grade_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('cRole.role_name')
                    ->label('Jabatan Fungsional')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jenjang_display')
                    ->getStateUsing(fn ($record) => preg_replace('/^(Penyuluh Hukum|Analis Hukum)\s+/i', '', $record->jabatan))
                    ->label('Jenjang')
                    ->searchable(['jabatan'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_kerja')
                    ->label('Unit Kerja')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('provinsi')
                    ->label('Provinsi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('instansi')
                    ->label('Instansi')
                    ->getStateUsing(fn (MasterJf $record) => $record->agenciable?->name ?: $record->instansi)
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
                Tables\Filters\SelectFilter::make('c_role_id')
                    ->label('Jabatan Fungsional')
                    ->options(function () {
                        $user = auth()->user();
                        $query = CRole::query()->where('active', true)->orderBy('role_name');
                        if ($user && ! $user->isSuperAdmin()) {
                            $allowedRoleIds = $user->adminAccesses()->pluck('c_role_id');
                            $query->whereIn('id', $allowedRoleIds);
                        }

                        return $query->pluck('role_name', 'id')->all();
                    })
                    ->visible(fn () => auth()->user() && auth()->user()->isSuperAdmin())
                    ->searchable(),
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
                Tables\Filters\SelectFilter::make('reg_grade_id')
                    ->label('Golongan/Ruang')
                    ->options(fn (): array => RegGrade::query()
                        ->orderBy('grade_code')
                        ->get()
                        ->mapWithKeys(fn (RegGrade $g) => [$g->id => $g->clean_name])
                        ->all())
                    ->searchable(),
                Tables\Filters\SelectFilter::make('jenjang')
                    ->label('Jenjang')
                    ->options([
                        'Ahli Pertama' => 'Ahli Pertama',
                        'Ahli Muda' => 'Ahli Muda',
                        'Ahli Madya' => 'Ahli Madya',
                        'Ahli Utama' => 'Ahli Utama',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->whereRaw('LOWER(jabatan) LIKE ?', ['%'.strtolower($data['value'])]);
                    })
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
                Tables\Filters\SelectFilter::make('provinsi')
                    ->label('Provinsi')
                    ->options(fn (): array => MasterJf::distinctOptions('provinsi'))
                    ->searchable()
                    ->multiple(),

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
                                ->body($e->getMessage() ?: 'Terjadi kendala saat membaca file. Silakan upload ulang.')
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
