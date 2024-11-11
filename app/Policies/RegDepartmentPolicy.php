<?php

namespace App\Policies;

use App\Models\User;
use App\Models\RegDepartment;
use Illuminate\Auth\Access\HandlesAuthorization;

class RegDepartmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_reg::department');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RegDepartment $regDepartment): bool
    {
        return $user->can('view_reg::department');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_reg::department');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RegDepartment $regDepartment): bool
    {
        return $user->can('update_reg::department');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RegDepartment $regDepartment): bool
    {
        return $user->can('delete_reg::department');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_reg::department');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, RegDepartment $regDepartment): bool
    {
        return $user->can('force_delete_reg::department');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_reg::department');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, RegDepartment $regDepartment): bool
    {
        return $user->can('restore_reg::department');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_reg::department');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, RegDepartment $regDepartment): bool
    {
        return $user->can('replicate_reg::department');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_reg::department');
    }
}
