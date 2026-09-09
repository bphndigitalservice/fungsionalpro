<?php

namespace App\Filament\Exports;

use App\Enums\ClientCluster;
use App\Models\Client;
use App\Models\RegProvinceEchelon1;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Support\Contracts\HasLabel;
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
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? $state),

            ExportColumn::make('agenciable.name')
                ->label('Instansi'),

            ExportColumn::make('unit_kerja')
                ->label('Unit Kerja')
                ->state(function (Client $record) {
                    return match ($record->type) {
                        ClientCluster::LocalRegency => $record->echelon_x_text,
                        ClientCluster::LocalProvince => is_numeric($record->echelon_x_text)
                            ? (once(fn () => RegProvinceEchelon1::query()->pluck('name', 'id'))[$record->echelon_x_text] ?? null)
                            : null,
                        default => $record->echelonable?->name,
                    };
                }),

            ExportColumn::make('status')
                ->label('Status Pegawai')
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? $state),

            ExportColumn::make('assignation_type')
                ->label('Jenis Pengangkatan')
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? $state),

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
