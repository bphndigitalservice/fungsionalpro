<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithClientPageTable;
use App\Models\Client;
use App\Services\ClientAccessService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ClientNumbersOverview extends StatsOverviewWidget
{
    use InteractsWithClientPageTable;

    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Jumlah Klien';

    protected function getStats(): array
    {
        $count = $this->getPageTableQuery()
            ->toBase()
            ->reorder()
            ->getCountForPagination();

        return [
            Stat::make('Total Klien', number_format($count))
                ->icon('heroicon-o-users'),
        ];
    }
}
