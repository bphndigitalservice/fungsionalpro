<?php

namespace App\Filament\Exports;

use App\Models\UkomApplication;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class UkomApplicationExporter extends Exporter
{
    protected static ?string $model = UkomApplication::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nama')->label('Nama'),
            ExportColumn::make('nip')->label('NIP'),
            ExportColumn::make('agenciable.name')->label('Instansi'),
            ExportColumn::make('current_jabatan')->label('Jabatan Saat Ini'),
            ExportColumn::make('targetCRole.role_name')->label('Daftar Sebagai'),
            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? $state),
            ExportColumn::make('created_at')
                ->label('Diajukan Pada'),
            ExportColumn::make('instansi_decision')
                ->label('Diterima/Ditolak pada (Instansi)')
                ->state(fn (UkomApplication $record) => $record->instansiDecisionDisplay()),
            ExportColumn::make('pembina_decision')
                ->label('Diterima/Ditolak pada (Instansi pembina)')
                ->state(fn (UkomApplication $record) => $record->pembinaDecisionDisplay()),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Ekspor pengajuan ukom selesai: '.$export->successful_rows.' baris.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.$failedRowsCount.' baris gagal.';
        }

        return $body;
    }
}
