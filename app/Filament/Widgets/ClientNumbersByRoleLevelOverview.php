<?php

namespace App\Filament\Widgets;

use App\Models\CRoleLevel;
use App\Services\ClientAccessService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Facades\DB;

class ClientNumbersByRoleLevelOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Klien berdasarkan Jenjang Jabatan';

    protected function getCards(): array
    {
        $query = $this->baseClientQuery();

        $rows = $query
            ->toBase()
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
        return app(ClientAccessService::class)->scopedQuery(auth()->user());
    }
}
