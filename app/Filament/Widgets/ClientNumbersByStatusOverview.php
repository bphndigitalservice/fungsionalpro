<?php

namespace App\Filament\Widgets;

use App\Enums\ClientStatus;
use App\Filament\Widgets\Concerns\InteractsWithClientPageTable;
use App\Services\ClientAccessService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ClientNumbersByStatusOverview extends StatsOverviewWidget
{
    use InteractsWithClientPageTable;

    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Klien berdasarkan Status';

    protected function getStats(): array
    {
        $counts = $this->getPageTableQuery()
            ->toBase()
            ->reorder()
            ->select('clients.status', DB::raw('COUNT(*) as total'))
            ->groupBy('clients.status')
            ->pluck('total', 'status');

        $stats = [];
        foreach (ClientStatus::cases() as $status) {
            $count = (int) ($counts[$status->value] ?? 0);
            $stats[] = Stat::make($status->getLabel(), number_format($count))
                ->icon(match ($status) {
                    ClientStatus::Active => 'heroicon-o-check-circle',
                    default => 'heroicon-o-x-circle',
                });
        }

        return $stats;
    }
}
