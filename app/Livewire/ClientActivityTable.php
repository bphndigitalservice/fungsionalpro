<?php

namespace App\Livewire;

use Filament\Widgets\TableWidget;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction;
use App\Models\ClientActivity;


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
                    : ClientActivity::query()->whereRaw('1 = 0') // empty but valid query
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Nama Kegiatan'),

                Tables\Columns\TextColumn::make('start_period')
                    ->label('Tanggal Mulai')
                    ->date(),

                Tables\Columns\TextColumn::make('end_period')
                    ->label('Tanggal Selesai')
                    ->date(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->wrap(),
            ])
            ->actions([
                Action::make('bukti')
                    ->label('Bukti Kegiatan')
                    ->color('primary')
                    ->url(fn (ClientActivity $record) =>
                        Storage::disk('s3')->temporaryUrl(
                            $record->activity_file,
                            now()->addHours(2),
                        )
                    )
                    ->openUrlInNewTab()
                    ->visible(fn (ClientActivity $record) => filled($record->activity_file)),
            ]);
    }
}
