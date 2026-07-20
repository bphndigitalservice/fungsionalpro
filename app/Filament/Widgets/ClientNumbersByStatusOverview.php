<?php

namespace App\Filament\Widgets;

use App\Enums\ClientStatus;
use App\Enums\SystemRole;
use App\Models\AdminAccess;
use App\Models\Client;
use App\Models\VerifierAccess;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Database\Query\JoinClause;
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
        $principal = auth()->user();

        $query = Client::query();

        if (method_exists($principal, 'isSuperAdmin') && $principal->isSuperAdmin()) {
            return $query;
        }

        if ($principal->hasAnySystemRole(SystemRole::Admin, SystemRole::AdminInstansi)) {
            $adminAccess = AdminAccess::query()->where('user_id', $principal->id);

            return $query->joinSub($adminAccess, 'aa', function (JoinClause $join) {
                $join->on('clients.c_role_id', '=', 'aa.c_role_id');
            });
        }

        if ($principal->hasAnySystemRole(SystemRole::AdminRegional, SystemRole::Verifier, SystemRole::AdminPusat, SystemRole::AdminInstansi)) {
            $verifierAccess = VerifierAccess::query()->where('user_id', $principal->id);

            return $query->joinSub($verifierAccess, 'va', function (JoinClause $join) {
                $join->on('clients.c_role_id', '=', 'va.c_role_id');
                $join->on('va.entity_type', '=', 'clients.agency_type');
                $join->on('va.entity_id', '=', 'clients.agency_id');
            });
        }

        return $query->whereRaw('1 = 0');
    }
}
