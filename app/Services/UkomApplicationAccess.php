<?php

namespace App\Services;

use App\Enums\SystemRole;
use App\Enums\UkomApplicationStatus;
use App\Models\UkomApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class UkomApplicationAccess
{
    public function canSubmit(?User $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->isActiveClient() || $user->isActiveCalonJf();
    }

    public function canFillForm(?User $user = null): bool
    {
        return UkomApplication::clientMaySubmit($user);
    }

    public function scopedQuery(User $user): Builder
    {
        $query = UkomApplication::query()->where('status', '!=', UkomApplicationStatus::Draft->value);

        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->hasSystemRole(SystemRole::Admin) && ! $this->isInstansiOnly($user)) {
            return $query;
        }

        if ($user->hasSystemRole(SystemRole::AdminInstansi)) {
            return $query->whereExists(function ($sub) use ($user): void {
                $sub->selectRaw('1')
                    ->from('admin_accesses as aa')
                    ->whereColumn('aa.c_role_id', 'ukom_applications.target_c_role_id')
                    ->where('aa.user_id', $user->id)
                    ->where(function ($scope): void {
                        $scope->whereNull('aa.entity_id')
                            ->orWhere(function ($entity): void {
                                $entity->whereColumn('aa.entity_type', 'ukom_applications.agency_type')
                                    ->whereColumn('aa.entity_id', 'ukom_applications.agency_id');
                            });
                    });
            });
        }

        return UkomApplication::query()->whereRaw('1 = 0');
    }

    public function canForward(User $user, UkomApplication $application): bool
    {
        if ($application->status !== UkomApplicationStatus::PendingInstansi) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($this->isInstansiOnly($user)) {
            return $this->scopedQuery($user)->whereKey($application->getKey())->exists();
        }

        return false;
    }

    public function canFinalDecide(User $user, UkomApplication $application): bool
    {
        if ($application->status !== UkomApplicationStatus::PendingAdmin) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasSystemRole(SystemRole::Admin) && ! $this->isInstansiOnly($user)) {
            return $this->scopedQuery($user)->whereKey($application->getKey())->exists();
        }

        return false;
    }

    public function isInstansiOnly(User $user): bool
    {
        return $user->hasSystemRole(SystemRole::AdminInstansi)
            && ! $user->hasSystemRole(SystemRole::Admin);
    }
}
