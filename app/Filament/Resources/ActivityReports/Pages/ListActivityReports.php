<?php

namespace App\Filament\Resources\ActivityReports\Pages;

use Filament\Schemas\Components\Tabs\Tab;
use App\Filament\Resources\ActivityReports\ActivityReportResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListActivityReports extends ListRecords
{
    protected static string $resource = ActivityReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    public function getTabs(): array
    {
        return [
            'diproses' => Tab::make('Diproses')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->whereNull('is_verified')
                ),

            'terverifikasi' => Tab::make('Terverifikasi')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->where('is_verified', true)
                ),

            'ditolak' => Tab::make('Ditolak')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->where('is_verified', false)
                ),
        ];
    }
}
