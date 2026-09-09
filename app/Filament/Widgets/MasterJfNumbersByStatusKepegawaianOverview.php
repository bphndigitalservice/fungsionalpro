<?php

namespace App\Filament\Widgets;

use App\Enums\JenisKepegawaian;
use App\Filament\Widgets\Concerns\InteractsWithMasterJfPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class MasterJfNumbersByStatusKepegawaianOverview extends StatsOverviewWidget
{
    use InteractsWithMasterJfPageTable;

    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Master JF berdasarkan Status Kepegawaian';

    protected function getStats(): array
    {
        $counts = $this->getPageTableQuery()
            ->toBase()
            ->reorder()
            ->select('status_kepegawaian', DB::raw('COUNT(*) as total'))
            ->groupBy('status_kepegawaian')
            ->pluck('total', 'status_kepegawaian');

        $stats = [];

        foreach (JenisKepegawaian::cases() as $jenis) {
            $stats[] = Stat::make($jenis->getLabel(), number_format((int) ($counts[$jenis->value] ?? 0)))
                ->icon('heroicon-o-identification');
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
