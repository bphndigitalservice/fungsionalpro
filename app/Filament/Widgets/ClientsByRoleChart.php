<?php

namespace App\Filament\Widgets;

use App\Enums\SystemRole;
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
    protected static ?int $height = 320;
    
    
    protected function getData(): array
    {
        $principal = auth()->user();

        // Build base query selecting role and count
        $query = Client::query()->select('clients.c_role_id', DB::raw('COUNT(*) as total'));

        if (method_exists($principal, 'isSuperAdmin') && $principal->isSuperAdmin()) {
            // no additional constraints
        } elseif ($principal->hasSystemRole(SystemRole::Admin)) {
            $adminAccess = AdminAccess::query()->where('user_id', $principal->id)->limit(1);
            $query->joinSub($adminAccess, 'aa', function (JoinClause $join) {
                $join->on('clients.c_role_id', '=', 'aa.c_role_id');
            });
        } elseif ($principal->hasAnySystemRole(SystemRole::AdminRegional, SystemRole::Verifier, SystemRole::AdminPusat)) {
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

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,

            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],

            'scales' => [
                'x' => [
                    'ticks' => [
                        'autoSkip' => false,
                        'maxRotation' => 0,
                        'minRotation' => 0,
                        'font' => [
                            'size' => 11,
                        ],
                    ],
                    'grid' => [
                        'display' => false,
                    ],
                ],

                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                    'grid' => [
                        'color' => '#e5e7eb',
                    ],
                ],
            ],

            'layout' => [
                'padding' => 10,
            ],
        ];
    }
}
