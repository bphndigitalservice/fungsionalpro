<?php

namespace App\Filament\Pages\Authx;

use Filament\Facades\Filament;

class RegistrationResponse implements \Filament\Http\Responses\Auth\Contracts\RegistrationResponse
{

    /**
     * @inheritDoc
     */
    public function toResponse($request)
    {
        return redirect()->intended(Filament::getUrl());
    }
}
