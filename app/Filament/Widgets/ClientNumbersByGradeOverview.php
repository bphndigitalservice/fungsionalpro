<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithClientPageTable;
use App\Models\RegGrade;
use App\Services\ClientAccessService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ClientNumbersByGradeOverview extends StatsOverviewWidget
{
    use InteractsWithClientPageTable;

    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Klien berdasarkan Pangkat/Golongan';

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();

        $rows = $query
            ->toBase()
            ->reorder()
            ->select('clients.reg_grade_id', DB::raw('COUNT(*) as total'))
            ->groupBy('clients.reg_grade_id')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $gradeNames = RegGrade::whereIn('id', $rows->pluck('reg_grade_id')->filter()->all())
            ->pluck('grade_name', 'id');

        $stats = [];
        foreach ($rows as $row) {
            $label = $row->reg_grade_id ? ($gradeNames[$row->reg_grade_id] ?? ('Grade #' . $row->reg_grade_id)) : 'Tidak diketahui';
            $stats[] = Stat::make($label, number_format((int) $row->total))
                ->icon('heroicon-o-academic-cap');
        }

        return $stats;
    }
}
