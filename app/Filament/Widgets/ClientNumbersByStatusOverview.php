<?php

namespace App\Filament\Widgets;

use App\Enums\ClientStatus;
use App\Models\AdminAccess;
use App\Models\Client;
use App\Models\VerifierAccess;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Database\Query\JoinClause;

class ClientNumbersByStatusOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Klien berdasarkan Status';

    protected function getCards(): array
    {
        $base = $this->baseClientQuery();

        $cards = [];
        foreach (ClientStatus::cases() as $status) {
            $count = (clone $base)->where('clients.status', $status->value)->count();
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
