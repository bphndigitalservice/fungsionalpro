<?php

namespace App\Filament\Widgets;

use App\Enums\SystemRole;
use App\Models\AdminAccess;
use App\Models\Client;
use App\Models\RegGrade;
use App\Models\VerifierAccess;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class ClientNumbersByGradeOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Klien berdasarkan Pangkat/Golongan';

    protected function getCards(): array
    {
        $query = $this->baseClientQuery();

        $rows = $query
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
        $principal = auth()->user();

        $query = Client::query();

        if (method_exists($principal, 'isSuperAdmin') && $principal->isSuperAdmin()) {
            return $query;
        }

        if ($principal->hasSystemRole(SystemRole::Admin)) {
            $adminAccess = AdminAccess::query()->where('user_id', $principal->id)->limit(1);

            return $query->joinSub($adminAccess, 'aa', function (JoinClause $join) {
                $join->on('clients.c_role_id', '=', 'aa.c_role_id');
            })->select('clients.*');
        }

        if ($principal->hasAnySystemRole(SystemRole::AdminRegional, SystemRole::Verifier, SystemRole::AdminPusat)) {
            $verifierAccess = VerifierAccess::query()->where('user_id', $principal->id)->limit(1);

            return $query->joinSub($verifierAccess, 'va', function (JoinClause $join) {
                $join->on('clients.c_role_id', '=', 'va.c_role_id');
                $join->on('va.entity_type', '=', 'clients.agency_type');
                $join->on('va.entity_id', '=', 'clients.agency_id');
            })->select('clients.*');
        }

        return $query->whereRaw('1 = 0');
    }
}
