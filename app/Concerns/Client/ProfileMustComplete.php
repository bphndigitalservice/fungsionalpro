<?php

namespace App\Concerns\Client;

use App\Models\Client;

trait ProfileMustComplete
{
    public function hasLatestEducation(): bool
    {
        return $this->education()->exists();
    }

    public function hasIdentityCompleted():bool
    {
        return $this->identity()->exists();
    }

    public function hasSKCPNSUploaded(): bool
    {
        return false;
    }

    public function getClientId(): ?string
    {
        if (auth()->user()->isActiveClient()) {
            return Client::where('user_id', auth()->user()->id)->first()->id;
        }

        return null;
    }
}
