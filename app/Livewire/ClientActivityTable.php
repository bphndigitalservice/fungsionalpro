<?php

namespace App\Livewire;

use App\Enums\Acceptance;
use App\Models\ClientActivity;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ClientActivityTable extends TableWidget
{
    protected ?Model $record = null;

    protected static ?string $heading = 'Daftar Kegiatan';

    public function mount(Model $record): void
    {
        $this->record = $record;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->record
                    ? $this->record->activities()->getQuery()
                    : ClientActivity::query()->whereRaw('1 = 0')
            )
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
                    ->visible(fn () => $this->record?->c_role_id == 1),

                TextColumn::make('jam')
                    ->label('Jam')
                    ->getStateUsing(fn ($record) => $record->start_time.' - '.$record->end_time
                    )
                    ->visible(fn () => $this->record?->c_role_id == 2),

                TextColumn::make('activity_details.lokasi')
                    ->label('Lokasi')
                    ->visible(fn () => $this->record?->c_role_id == 2)
                    ->wrap(),

                TextColumn::make('activity_details.jumlah_peserta')
                    ->label('Peserta')
                    ->visible(fn () => $this->record?->c_role_id == 2),

                TextColumn::make('activity_details.penerima')
                    ->label('Penerima')
                    ->visible(fn () => $this->record?->c_role_id == 2)
                    ->wrap(),

                TextColumn::make('activity_details.materi')
                    ->label('Materi')
                    ->wrap()
                    ->limit(100)
                    ->tooltip(fn ($record) => $record->description)
                    ->visible(fn () => $this->record?->c_role_id == 2),

                TextColumn::make('description')
                    ->label('Deskripsi Kegiatan')
                    ->wrap()
                    ->limit(100)
                    ->tooltip(fn ($record) => $record->description),

                TextColumn::make('is_verified')
                    ->label('Status Verifikasi')
                    ->getStateUsing(fn (?ClientActivity $record): string => match ($record?->is_verified) {
                        Acceptance::Accept => 'Sudah Diverifikasi',
                        Acceptance::Reject => 'Ditolak',
                        default => 'Belum Diverifikasi',
                    }),

            ])
            ->actions([
                Action::make('bukti')
                    ->label('Lampiran Laporan Kegiatan')
                    ->color('primary')
                    ->url(fn (ClientActivity $record) => Storage::disk('s3')->temporaryUrl(
                        $record->activity_file,
                        now()->addHours(2),
                    )
                    )
                    ->openUrlInNewTab()
                    ->visible(fn (ClientActivity $record) => filled($record->activity_file)),
            ]);
    }
}
