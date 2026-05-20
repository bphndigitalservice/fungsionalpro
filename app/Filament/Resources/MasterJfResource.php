<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MasterJfResource\Pages;
use App\Filament\Resources\MasterJfResource\RelationManagers;
use App\Models\MasterJf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;

use App\Imports\MasterJfImport;

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
                Forms\Components\TextInput::make('instansi'),
                Forms\Components\TextInput::make('unit_kerja'),
                Forms\Components\Select::make('pengangkatan')
                    ->label('Pengangkatan')
                    ->options([
                        'CPNS/PPPK' => 'CPNS/PPPK',
                        'Inpassing' => 'Inpassing',
                        'PDJL' => 'PDJL',
                        'Penyetaraan' => 'Penyetaraan',
                    ])
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('status')
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
                    ->preload()
            ]);
    }

   public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->searchable(),

                Tables\Columns\TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable(),

                Tables\Columns\TextColumn::make('gol_ruang')
                    ->label('Golongan/Ruang')
                    ->searchable(),

                Tables\Columns\TextColumn::make('jabatan')
                    ->label('Jabatan')
                    ->searchable(),

                Tables\Columns\TextColumn::make('unit_kerja')
                    ->label('Unit Kerja')
                    ->searchable(),

                Tables\Columns\TextColumn::make('instansi')
                    ->label('Instansi')
                    ->searchable(),

                Tables\Columns\TextColumn::make('pengangkatan')
                    ->label('Pengangkatan')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status'),
            ])

            ->headerActions([
                Action::make('import')
                    ->label('Import Data')

                    ->form([
                        FileUpload::make('file')
                            ->label('File Excel')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                            ])
                            ->maxSize(5120)
                            ->helperText('Format file: .xlsx atau .xls. Maksimal ukuran file 5 MB.')
                            ->required(),
                    ])

                    ->action(function (array $data) {
                        try {

                            Excel::import(
                                new MasterJfImport,
                                storage_path('app/public/' . $data['file'])
                            );

                            Notification::make()
                                ->title('Import berhasil')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {

                            $message = $e->getMessage();

                            if (
                                str_contains($message, 'TemporaryUploadedFile') ||
                                str_contains($message, 'ReaderType') ||
                                str_contains($message, 'File does not exist')
                            ) {
                                $message = 'Terjadi kendala saat membaca file. Silakan upload ulang.';
                            }

                            Notification::make()
                                ->title('Import gagal')
                                ->body($message)
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
        ->actions([
            Tables\Actions\EditAction::make(),
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
        return __('Import Data Klien');
    }
    
    public static function getModelLabel(): string
    {
        return __('Import Data Klien');
    }
}
