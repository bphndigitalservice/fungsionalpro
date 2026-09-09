<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithMasterJfPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class MasterJfNumbersByJenjangOverview extends StatsOverviewWidget
{
    use InteractsWithMasterJfPageTable;

    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Master JF berdasarkan Jenjang';

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();

        $count = fn (string $jenjang) => (clone $query)
            ->whereRaw('LOWER(jabatan) LIKE ?', ['%' . strtolower($jenjang)])
            ->count();

        return [
            Stat::make('Ahli Pertama', number_format($count('Ahli Pertama')))
                ->icon('heroicon-o-academic-cap'),
            Stat::make('Ahli Muda', number_format($count('Ahli Muda')))
                ->icon('heroicon-o-academic-cap'),
            Stat::make('Ahli Madya', number_format($count('Ahli Madya')))
                ->icon('heroicon-o-academic-cap'),
            Stat::make('Ahli Utama', number_format($count('Ahli Utama')))
                ->icon('heroicon-o-academic-cap'),
        ];
    }
}
