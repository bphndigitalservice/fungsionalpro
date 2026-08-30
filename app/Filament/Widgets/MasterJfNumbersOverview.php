<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithMasterJfPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MasterJfNumbersOverview extends StatsOverviewWidget
{
    use InteractsWithMasterJfPageTable;

    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Jumlah Master JF';

    protected function getStats(): array
    {
        $count = $this->getPageTableQuery()
            ->toBase()
            ->reorder()
            ->getCountForPagination();

        return [
            Stat::make('Total Master JF', number_format($count))
                ->icon('heroicon-o-users'),
        ];
    }
}
