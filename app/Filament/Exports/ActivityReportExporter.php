<?php

namespace App\Filament\Exports;

use App\Models\ClientActivity;
use App\Models\RegProvince;
use App\Filament\Resources\ClientActivityResource;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Carbon\Carbon;
use OpenSpout\Common\Entity\Style\Style;

class ActivityReportExporter extends Exporter
{
    protected static ?string $model = ClientActivity::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('No. Sistem'),

            // Pulls the employee/client's name for the admin view
            ExportColumn::make('client.identity.name')
                ->label('Nama Pegawai / Client'),
                
            ExportColumn::make('title')
                ->label('Kegiatan'),

            // Reuses the types central array from your ClientActivityResource
            ExportColumn::make('jenis_kegiatan')
                ->label('Jenis Kegiatan')
                ->formatStateUsing(fn ($state) => ClientActivityResource::getJenisKegiatanOptions()[(int)$state] ?? '-'),

            ExportColumn::make('reg_province_id')
                ->label('Provinsi Pelaksanaan')
                ->formatStateUsing(fn ($state) => $state ? RegProvince::find($state)?->name ?? '-' : '-'),

            ExportColumn::make('start_period')
                ->label('Tanggal Mulai')
                ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d-m-Y') : '-'),

            ExportColumn::make('end_period')
                ->label('Tanggal Selesai')
                ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d-m-Y') : '-'),

            ExportColumn::make('jam')
                ->label('Waktu/Jam')
                ->getStateUsing(fn (ClientActivity $record) => 
                    $record->start_time && $record->end_time 
                        ? Carbon::parse($record->start_time)->format('H:i') . ' - ' . Carbon::parse($record->end_time)->format('H:i')
                        : '-'
                ),

            ExportColumn::make('activity_details.lokasi')
                ->label('Lokasi'),

            ExportColumn::make('activity_details.jumlah_peserta')
                ->label('Peserta')
                ->formatStateUsing(fn ($state) => $state ? number_format((int)$state, 0, ',', '.') : '0'),

            ExportColumn::make('activity_details.penerima')
                ->label('Penerima'),

            ExportColumn::make('activity_details.materi')
                ->label('Materi'),

            ExportColumn::make('description')
                ->label('Deskripsi Kegiatan'),

            ExportColumn::make('is_verified')
                ->label('Status Verifikasi')
                ->formatStateUsing(function ($state) {
                    if (is_null($state)) return 'Sedang Diverifikasi';
                    return $state ? 'Terverifikasi' : 'Ditolak';
                }),
        ];
    }

    /**
     * Styles the main spreadsheet data rows neatly
     */
    public function getXlsxCellStyle(): ?Style
    {
        return (new Style())
            ->setFontSize(11)
            ->setFontName('Arial')
            ->setShouldWrapText(false);
    }

    /**
     * Styles the top table headers explicitly to make them bold and legible
     */
    public function getXlsxHeaderCellStyle(): ?Style
    {
        return (new Style())
            ->setFontBold()
            ->setFontSize(12)
            ->setFontName('Arial')
            ->setShouldWrapText(false);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Ekspor spreadsheet (.xlsx) pelaporan kegiatan admin telah selesai.';
    }
}