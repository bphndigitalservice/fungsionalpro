<?php

namespace App\Filament\Widgets;

use App\Models\AdminAccess;
use App\Models\Client;
use App\Models\CRoleLevel;
use App\Models\VerifierAccess;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class ClientNumbersByRoleLevelOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Klien berdasarkan Jenjang Jabatan';

    protected function getCards(): array
    {
        $query = $this->baseClientQuery();

        $rows = $query
            ->select('clients.c_role_level_id', DB::raw('COUNT(*) as total'))
            ->groupBy('clients.c_role_level_id')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $levelNames = CRoleLevel::whereIn('id', $rows->pluck('c_role_level_id')->filter()->all())
            ->pluck('level', 'id');

        $cards = [];
        foreach ($rows as $row) {
            $label = $row->c_role_level_id ? ($levelNames[$row->c_role_level_id] ?? ('Level #' . $row->c_role_level_id)) : 'Tidak diketahui';
            $cards[] = Card::make($label, number_format((int) $row->total))
                ->icon('heroicon-o-briefcase');
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

        if ($principal->hasRole('admin')) {
            $adminAccess = AdminAccess::query()->where('user_id', $principal->id)->limit(1);

            return $query->joinSub($adminAccess, 'aa', function (JoinClause $join) {
                $join->on('clients.c_role_id', '=', 'aa.c_role_id');
            })->select('clients.*');
        }

        if ($principal->hasRole(['admin-regional', 'verifier','admin-pusat'])) {
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
