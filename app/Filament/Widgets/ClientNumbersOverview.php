<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Client;
use App\Services\ClientAccessService;
use Filament\Widgets\StatsOverviewWidget;

class ClientNumbersOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Jumlah Klien';

    protected function getCards(): array
    {
        $count = $this->baseClientQuery()->toBase()->count();

        return [
            Stat::make('Total Klien', number_format($count))
                ->icon('heroicon-o-users'),
        ];
    }

    protected function baseClientQuery()
    {
        return app(ClientAccessService::class)->scopedQuery(auth()->user());
    }
}
