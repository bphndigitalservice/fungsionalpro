<?php

namespace App\Filament\Resources;

use App\Enums\SystemRole;
use App\Filament\Resources\ActivityReportResource\Pages;
use App\Filament\Resources\ActivityReportResource\RelationManagers;
use App\Models\ActivityReport;
use App\Models\AdminAccess;
use App\Models\Client;
use App\Models\ClientActivity;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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

    public static function form(Form $form): Form
    {
        return $form->schema(

        static::getFormSchema()

        );
    }

    public static function getFormSchema(): array
    {
        return
            [
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
                    ])
            ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('client.identity.name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Kegiatan')
                    ->wrap()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi Kegiatan')
                    ->wrap()
                    ->limit(200)
                    ->tooltip(
                        fn ($record)=>$record->description
                    ),

                Tables\Columns\TextColumn::make('is_verified')
                    ->label('Status Verifikasi')
                    ->badge()
                    ->getStateUsing(fn ($record)=>
                        is_null($record->is_verified)
                            ? 'Sedang Diverifikasi'
                            : ($record->is_verified
                                ? 'Terverifikasi'
                                : 'Ditolak'))
                    ->color(fn ($record)=>
                        is_null($record->is_verified)
                            ? 'gray'
                            : ($record->is_verified
                                ? 'success'
                                : 'danger'))
                    ->tooltip(function ($record){
                        if ($record?->is_verified === false){
                            return "Alasan Penolakan: {$record->verification_note}";
                        }
                        if ($record?->is_verified === true){
                            return 'Kegiatan telah diverifikasi';
                        }
                        return 'Menunggu verifikasi';
                    }),
            ])          

            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail')
                    ->modalHeading('Detail Kegiatan'), 
                MediaAction::make()
                    ->media(fn(Model $record) => Storage::temporaryUrl($record->activity_file, now()->addMinutes(10)))
                    ->label('Lampiran Laporan Kegiatan'),
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


        $allowedRoleIds = AdminAccess::where('user_id', $user->id)
            ->pluck('c_role_id')
            ->toArray();

        return parent::getEloquentQuery()
            ->whereHas('client', function (Builder $query) use ($allowedRoleIds) {
                $query->whereIn('c_role_id', $allowedRoleIds);
            });
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        return $user
            && (
                $user->hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin, SystemRole::AdminInstansi)
            );
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user
            && (
                $user->hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin, SystemRole::AdminInstansi)
            );
    }

}