<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\CRole;
use App\Models\VerifierAccess;
use App\Models\AdminAccess;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class ClientsByRoleChart extends ChartWidget
{
    protected static ?string $heading = 'Jabatan Fungsional';

    protected function getData(): array
    {
        $principal = auth()->user();

        // Build base query selecting role and count
        $query = Client::query()->select('clients.c_role_id', DB::raw('COUNT(*) as total'));

        if (method_exists($principal, 'isSuperAdmin') && $principal->isSuperAdmin()) {
            // no additional constraints
        } elseif ($principal->hasRole('admin')) {
            $adminAccess = AdminAccess::query()->where('user_id', $principal->id)->limit(1);
            $query->joinSub($adminAccess, 'aa', function (JoinClause $join) {
                $join->on('clients.c_role_id', '=', 'aa.c_role_id');
            });
        } elseif ($principal->hasRole(['admin-regional', 'verifier','admin-pusat'])) {
            $verifierAccess = VerifierAccess::query()->where('user_id', $principal->id)->limit(1);
            $query->joinSub($verifierAccess, 'va', function (JoinClause $join) {
                $join->on('clients.c_role_id', '=', 'va.c_role_id');
                $join->on('va.entity_type', '=', 'clients.agency_type');
                $join->on('va.entity_id', '=', 'clients.agency_id');
            });
        } else {
            // For other roles, show nothing
            return [
                'datasets' => [
                    [
                        'label' => 'Jabatan Fungsional',
                        'data' => [],
                    ],
                ],
                'labels' => [],
            ];
        }

        $rows = $query->groupBy('clients.c_role_id')
            ->orderBy('clients.c_role_id')
            ->get();

        // Map role IDs to role names for labels
        $roleNames = CRole::whereIn('id', $rows->pluck('c_role_id')->filter()->all())
            ->pluck('role_name', 'id');

        $labels = [];
        $data = [];

        foreach ($rows as $row) {
            $labels[] = $roleNames[$row->c_role_id] ?? ('Role #' . $row->c_role_id);
            $data[] = (int) $row->total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jabatan Fungsional',
                    'data' => $data,
                    'backgroundColor' => '#6366f1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
