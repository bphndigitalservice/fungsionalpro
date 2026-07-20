<?php

namespace App\Filament\Pages\Authx;

use Filament\Facades\Filament;

class RegistrationResponse implements \Filament\Auth\Http\Responses\Contracts\RegistrationResponse
{

    public function toResponse($request)
    {
        return redirect()->intended(Filament::getUrl());
    }
}
