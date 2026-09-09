<?php

namespace App\Filament\Exports;

use App\Models\Client;
use App\Models\ClientActivity;
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
        $roleId = Client::current()?->c_role_id;

        $columns = [
            ExportColumn::make('client.identity.name')
                ->label('Nama'),

            ExportColumn::make('client.nip')
                ->label('NIP'),
        ];

        if ($roleId == 2) {
            $columns[] = ExportColumn::make('jenis_kegiatan')
                ->label('Jenis Kegiatan')
                ->formatStateUsing(fn ($state) => ClientActivityResource::getJenisKegiatanOptions()[(int)$state] ?? '-');
        }

        $columns[] = ExportColumn::make('title')
            ->label('Kegiatan');

        if ($roleId == 2) {
            $columns[] = ExportColumn::make('regProvince.name')
                ->label('Provinsi Pelaksanaan')
                ->default('-');
        }

        if ($roleId == 1) {
            $columns[] = ExportColumn::make('start_period')
                ->label('Tanggal Mulai')
                ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d-m-Y') : '-');

            $columns[] = ExportColumn::make('end_period')
                ->label('Tanggal Selesai')
                ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d-m-Y') : '-');
        } else {
            $columns[] = ExportColumn::make('start_period')
                ->label('Tanggal')
                ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d-m-Y') : '-');
        }

        if ($roleId == 2) {
            $columns[] = ExportColumn::make('jam')
                ->label('Waktu/Jam')
                ->getStateUsing(fn (ClientActivity $record) =>
                    $record->start_time && $record->end_time
                        ? Carbon::parse($record->start_time)->format('H:i') . ' - ' . Carbon::parse($record->end_time)->format('H:i')
                        : '-'
                );

            $columns[] = ExportColumn::make('activity_details.lokasi')
                ->label('Lokasi');

            $columns[] = ExportColumn::make('activity_details.jumlah_peserta')
                ->label('Peserta')
                ->formatStateUsing(fn ($state) => $state ? number_format((int)$state, 0, ',', '.') : '0');

            $columns[] = ExportColumn::make('activity_details.penerima')
                ->label('Penerima');

            $columns[] = ExportColumn::make('activity_details.materi')
                ->label('Materi');
        }

        $columns[] = ExportColumn::make('description')
            ->label('Deskripsi Kegiatan');

        $columns[] = ExportColumn::make('is_verified')
            ->label('Status Verifikasi')
            ->formatStateUsing(function ($state) {
                if (is_null($state)) return 'Sedang Diverifikasi';
                return $state ? 'Terverifikasi' : 'Ditolak';
            });

        return $columns;
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
