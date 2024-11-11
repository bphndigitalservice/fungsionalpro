<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\ClientPoint;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class PointOverview extends BaseWidget
{

    use HasWidgetShield;

    protected function getStats(): array
    {
        return [
            Stat::make(__('Angka Kredit'), $this->getPoint())
                ->descriptionIcon('heroicon-m-arrow-trending-up'),
        ];
    }

    protected function getPoint(): float
    {
        $client = $this->getClient();
        if (! is_null($client)) {
            $key = sprintf('point_%s', $client->id);

            return Cache::remember($key, 60, function () use ($client) {
                return ClientPoint::getPoint($client->id);
            });
        }

        return 0;
    }

    protected function getClient(): ?Client
    {
        $user = auth()->user();

        if ($user->isActiveClient()) {
            return $user->client;
        }

        return null;
    }

    public function getColumnSpan(): int|string|array
    {
        return 1;
    }

    protected function getColumns(): int
    {
        return 1;
    }


}
