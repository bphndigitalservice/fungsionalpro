<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithMasterJfPageTable;
use App\Models\MasterJf;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class MasterJfNumbersByStatusOverview extends StatsOverviewWidget
{
    use InteractsWithMasterJfPageTable;

    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Master JF berdasarkan Status';

    protected function getStats(): array
    {
        $counts = $this->getPageTableQuery()
            ->toBase()
            ->reorder()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [];

        foreach (MasterJf::statusOptions() as $value => $label) {
            $stats[] = Stat::make($label, number_format((int) ($counts[$value] ?? 0)))
                ->icon($value === 'Aktif' ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle');
        }

        $unknown = 0;
        foreach ($counts as $key => $total) {
            if ($key === null || $key === '') {
                $unknown += (int) $total;
            }
        }

        if ($unknown > 0) {
            $stats[] = Stat::make('Tidak diketahui', number_format($unknown))
                ->icon('heroicon-o-question-mark-circle');
        }

        return $stats;
    }
}
