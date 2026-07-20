<?php

namespace App\Filament\Widgets;

use App\Models\CRole;
use App\Services\ClientAccessService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ClientsByRoleChart extends ChartWidget
{
    protected ?string $heading = 'Jabatan Fungsional';
    protected static ?int $height = 320;

    protected function getData(): array
    {
        $principal = auth()->user();

        $query = app(ClientAccessService::class)
            ->scopedQuery($principal)
            ->select('clients.c_role_id', DB::raw('COUNT(*) as total'));

        // Empty scope (no access) still returns a query with whereRaw('1 = 0').
        $rows = $query->groupBy('clients.c_role_id')
            ->orderBy('clients.c_role_id')
            ->get();

        if ($rows->isEmpty()) {
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
