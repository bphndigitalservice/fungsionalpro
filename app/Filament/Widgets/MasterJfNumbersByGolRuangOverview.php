<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithMasterJfPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class MasterJfNumbersByGolRuangOverview extends StatsOverviewWidget
{
    use InteractsWithMasterJfPageTable;

    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Master JF berdasarkan Golongan/Ruang';

    protected function getStats(): array
    {
        $rows = $this->getPageTableQuery()
            ->toBase()
            ->reorder()
            ->select('gol_ruang', DB::raw('COUNT(*) as total'))
            ->groupBy('gol_ruang')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $stats = [];

        foreach ($rows as $row) {
            $label = $row->gol_ruang ?: 'Tidak diketahui';
            $stats[] = Stat::make($label, number_format((int) $row->total))
                ->icon('heroicon-o-academic-cap');
        }

        return $stats;
    }
}
