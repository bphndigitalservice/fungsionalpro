<?php

namespace App\Filament\Widgets;

use App\Enums\ClientStatus;
use App\Services\ClientAccessService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Facades\DB;

class ClientNumbersByStatusOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Klien berdasarkan Status';

    protected function getCards(): array
    {
        $counts = $this->baseClientQuery()
            ->toBase()
            ->select('clients.status', DB::raw('COUNT(*) as total'))
            ->groupBy('clients.status')
            ->pluck('total', 'status');

        $cards = [];
        foreach (ClientStatus::cases() as $status) {
            $count = (int) ($counts[$status->value] ?? 0);
            $cards[] = Card::make($status->getLabel(), number_format($count))
                ->icon(match ($status) {
                    ClientStatus::Active => 'heroicon-o-check-circle',
                    default => 'heroicon-o-x-circle',
                });
        }

        return $cards;
    }

    protected function baseClientQuery()
    {
        return app(ClientAccessService::class)->scopedQuery(auth()->user());
    }
}
