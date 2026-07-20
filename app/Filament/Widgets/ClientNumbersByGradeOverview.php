<?php

namespace App\Filament\Widgets;

use App\Models\RegGrade;
use App\Services\ClientAccessService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Facades\DB;

class ClientNumbersByGradeOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Klien berdasarkan Pangkat/Golongan';

    protected function getCards(): array
    {
        $query = $this->baseClientQuery();

        $rows = $query
            ->toBase()
            ->select('clients.reg_grade_id', DB::raw('COUNT(*) as total'))
            ->groupBy('clients.reg_grade_id')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $gradeNames = RegGrade::whereIn('id', $rows->pluck('reg_grade_id')->filter()->all())
            ->pluck('grade_name', 'id');

        $cards = [];
        foreach ($rows as $row) {
            $label = $row->reg_grade_id ? ($gradeNames[$row->reg_grade_id] ?? ('Grade #' . $row->reg_grade_id)) : 'Tidak diketahui';
            $cards[] = Card::make($label, number_format((int) $row->total))
                ->icon('heroicon-o-academic-cap');
        }

        return $cards;
    }

    protected function baseClientQuery()
    {
        return app(ClientAccessService::class)->scopedQuery(auth()->user());
    }
}
