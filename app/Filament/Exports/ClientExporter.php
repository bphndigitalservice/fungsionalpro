<?php

namespace App\Filament\Exports;

use App\Models\Client;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use OpenSpout\Common\Entity\Style\Style;

class ClientExporter extends Exporter
{
    protected static ?string $model = Client::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nip')
                ->label('NIP'),

            ExportColumn::make('identity.name')
                ->label('Nama Lengkap'),

            ExportColumn::make('crole.role_name')
                ->label('Jabatan'),

            ExportColumn::make('croleLevel.level')
                ->label('Jenjang Jabatan'),

            ExportColumn::make('point.point')
                ->label('Angka Kredit / Point'),

            ExportColumn::make('type')
                ->label('Kelompok Instansi')
                ->formatStateUsing(function ($state) {
                    $value = $state instanceof \BackedEnum ? $state->value : $state;
                    return match ($value) {
                        'central' => 'Pusat',
                        'local_province' => 'Daerah Provinsi',
                        'local_regency' => 'Daerah Kabupaten/Kota',
                        default => $value,
                    };
                }),

            ExportColumn::make('agenciable.name')
                ->label('Instansi'),

            ExportColumn::make('echelonable.name')
                ->label('Unit Kerja'),

            ExportColumn::make('status')
                ->label('Status Pegawai')
                ->formatStateUsing(function ($state) {
                    return $state instanceof \BackedEnum ? $state->value : $state;
                }),

            ExportColumn::make('assignation_type')
                ->label('Jenis Pengangkatan')
                ->formatStateUsing(function ($state) {
                    return $state instanceof \BackedEnum ? $state->value : $state;
                }),

            ExportColumn::make('jenis_kepegawaian')
                ->label('Jenis Kepegawaian'),
        ];
    }

    public function getXlsxCellStyle(): ?Style
    {
        return (new Style())
            ->setFontSize(11)
            ->setFontName('Arial')
            ->setShouldWrapText(false);
    }

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
        return 'Ekspor data master Pejabat Fungsional (.xlsx) telah selesai diproses.';
    }
}
