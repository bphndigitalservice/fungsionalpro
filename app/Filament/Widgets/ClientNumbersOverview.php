<?php

namespace App\Filament\Widgets;

use App\Enums\SystemRole;
use App\Models\AdminAccess;
use App\Models\Client;
use App\Models\VerifierAccess;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Database\Query\JoinClause;

class ClientNumbersOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Jumlah Klien';

    protected function getCards(): array
    {
        $count = $this->baseClientQuery()->count();

        return [
            Card::make('Total Klien', number_format($count))
                ->icon('heroicon-o-users'),
        ];
    }

    protected function baseClientQuery()
    {
        $principal = auth()->user();

        $query = Client::query();

        if (method_exists($principal, 'isSuperAdmin') && $principal->isSuperAdmin()) {
            return $query;
        }

        if ($principal->hasAnySystemRole(SystemRole::Admin, SystemRole::AdminInstansi)) {
            $adminAccess = AdminAccess::query()->where('user_id', $principal->id)->limit(1);

            return $query->joinSub($adminAccess, 'aa', function (JoinClause $join) {
                $join->on('clients.c_role_id', '=', 'aa.c_role_id');
            })->select('clients.*');
        }

        if ($principal->hasAnySystemRole(SystemRole::AdminRegional, SystemRole::Verifier, SystemRole::AdminPusat, SystemRole::AdminInstansi)) {
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
