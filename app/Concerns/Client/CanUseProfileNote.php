<?php

namespace App\Concerns\Client;

use App\Enums\Verified;
use App\Models\Client;
use App\Models\VClientNote;

trait CanUseProfileNote
{
    public function getProfileNote(): ?string
    {
        if (is_null($this->getClientId())){
            return null;
        }

        return VClientNote::where('client_id', $this->getClientId())->first()->verifier_notes;
    }

    public function getVerificationNote(): Verified
    {
        $status = Client::where('user_id', auth()->user()->id)->first()->is_verified ?? null;

        return is_null($status)
            ? Verified::Unverified
            : $status;
    }

    public function getClientId(): ?string
    {
        if (auth()->user()->isActiveClient())
            return Client::where('user_id', auth()->user()->id)->first()->id;

        return null;
    }
}
