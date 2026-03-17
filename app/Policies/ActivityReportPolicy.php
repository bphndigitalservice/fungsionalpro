<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ClientActivity;

class ActivityReportPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function viewAny(User $user)
    {
        return $user->hasRole(['admin','super_admin']);
    }
}
