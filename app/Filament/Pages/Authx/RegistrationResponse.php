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
        // If the user needs to verify their email, send them to the prompt directly
        if (Filament::auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && 
            ! Filament::auth()->user()->hasVerifiedEmail()) {
            return redirect()->route('filament.admin.auth.email-verification.prompt');
        }

        return redirect()->intended(Filament::getUrl());
    }
}
