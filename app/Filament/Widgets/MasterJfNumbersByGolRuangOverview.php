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
            ->select('reg_grade_id', DB::raw('COUNT(*) as total'))
            ->groupBy('reg_grade_id')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $gradeCodes = \App\Models\RegGrade::whereIn('id', $rows->pluck('reg_grade_id')->filter()->all())
            ->pluck('grade_code', 'id');

        $stats = [];

        foreach ($rows as $row) {
            $label = $row->reg_grade_id
                ? ($gradeCodes[$row->reg_grade_id] ?? ('Grade #' . $row->reg_grade_id))
                : 'Tidak diketahui';
            $stats[] = Stat::make($label, number_format((int) $row->total))
                ->icon('heroicon-o-academic-cap');
        }

        return $stats;
    }
}
