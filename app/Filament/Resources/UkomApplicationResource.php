<?php

namespace App\Filament\Resources;

use App\Enums\SystemRole;
use App\Filament\Resources\UkomApplicationResource\Pages;
use App\Infolists\Components\MinioFileEntry;
use App\Models\UkomApplication;
use App\Services\UkomApplicationAccess;
use App\Services\UkomApplicationService;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UkomApplicationResource extends Resource
{
    protected static ?string $model = UkomApplication::class;

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return __('Verifikasi');
    }

    public static function getNavigationLabel(): string
    {
        return __('labels.page.ukom_verification.nav');
    }

    public static function getModelLabel(): string
    {
        return 'Pengajuan Ukom';
    }

    public static function getPluralModelLabel(): string
    {
        return __('labels.page.ukom_verification.nav');
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        return $user && $user->hasAnySystemRole(
            SystemRole::Admin,
            SystemRole::AdminInstansi,
            SystemRole::SuperAdmin,
        );
    }

    public static function canViewAny(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        if ($user === null) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return app(UkomApplicationAccess::class)
            ->scopedQuery($user)
            ->with(['targetCRole', 'user', 'agenciable']);
    }

    public static function canView($record): bool
    {
        $user = Auth::user();

        if ($user === null) {
            return false;
        }

        return app(UkomApplicationAccess::class)
            ->scopedQuery($user)
            ->whereKey($record->getKey())
            ->exists();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Pemohon')
                    ->schema([
                        Infolists\Components\TextEntry::make('nama')->label('Nama'),
                        Infolists\Components\TextEntry::make('nip')->label('NIP'),
                        Infolists\Components\TextEntry::make('current_jabatan')->label('Jabatan Saat Ini'),
                        Infolists\Components\TextEntry::make('targetCRole.role_name')->label('Daftar Sebagai'),
                        Infolists\Components\TextEntry::make('status')->badge(),
                        Infolists\Components\TextEntry::make('agenciable.name')->label('Instansi'),
                        Infolists\Components\TextEntry::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->visible(fn (UkomApplication $record) => filled($record->rejection_reason)),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Dokumen')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('documents')
                            ->schema([
                                Infolists\Components\TextEntry::make('documentType.label')->label('Jenis'),
                                MinioFileEntry::make('file_path')
                                    ->label('Berkas')
                                    ->disk('s3'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')->searchable(),
                Tables\Columns\TextColumn::make('nip')->searchable()->label('NIP'),
                Tables\Columns\TextColumn::make('instansi')
                    ->label('Instansi')
                    ->getStateUsing(fn (UkomApplication $record) => $record->agenciable?->name)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('current_jabatan')->label('Jabatan Saat Ini'),
                Tables\Columns\TextColumn::make('targetCRole.role_name')->label('Daftar Sebagai'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diajukan Pada')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                static::forwardAction(),
                static::acceptAction(),
                static::rejectAction(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUkomApplications::route('/'),
            'view' => Pages\ViewUkomApplication::route('/{record}'),
        ];
    }

    public static function forwardAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('forward')
            ->label(__('labels.page.ukom_verification.forward'))
            ->visible(fn (UkomApplication $record) => app(UkomApplicationAccess::class)->canForward(Auth::user(), $record))
            ->requiresConfirmation()
            ->modalHeading(__('labels.page.ukom_verification.forward'))
            ->modalDescription(__('labels.page.ukom_verification.forward_confirm'))
            ->modalSubmitActionLabel(__('labels.page.ukom_verification.forward'))
            ->action(function (UkomApplication $record) {
                app(UkomApplicationService::class)->forward(Auth::user(), $record);
                Notification::make()->success()->title(__('labels.page.ukom_verification.forwarded'))->send();
            });
    }

    public static function acceptAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('accept')
            ->label('Terima')
            ->color('success')
            ->visible(fn (UkomApplication $record) => app(UkomApplicationAccess::class)->canFinalDecide(Auth::user(), $record))
            ->requiresConfirmation()
            ->action(function (UkomApplication $record) {
                app(UkomApplicationService::class)->accept(Auth::user(), $record);
                Notification::make()->success()->title('Pengajuan diterima.')->send();
            });
    }

    public static function rejectAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('reject')
            ->label('Tolak')
            ->color('danger')
            ->visible(fn (UkomApplication $record) => app(UkomApplicationAccess::class)->canForward(Auth::user(), $record)
                || app(UkomApplicationAccess::class)->canFinalDecide(Auth::user(), $record))
            ->form([
                Forms\Components\Textarea::make('rejection_reason')
                    ->label('Alasan')
                    ->required(),
            ])
            ->action(function (UkomApplication $record, array $data) {
                try {
                    app(UkomApplicationService::class)->reject(Auth::user(), $record, $data['rejection_reason']);
                    Notification::make()->success()->title('Pengajuan ditolak.')->send();
                } catch (ValidationException $exception) {
                    Notification::make()->danger()->title(collect($exception->errors())->flatten()->first())->send();
                }
            });
    }
}
