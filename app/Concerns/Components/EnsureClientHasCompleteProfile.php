<?php

namespace App\Concerns\Components;

trait EnsureClientHasCompleteProfile
{

    public function hasCompleteProfile(): bool
    {
        return auth()->user()->isActiveClient();
    }

}
