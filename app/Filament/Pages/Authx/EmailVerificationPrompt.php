<?php

namespace App\Filament\Pages\Authx;


use Exception;
use Filament\Facades\Filament;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class EmailVerificationPrompt extends \Filament\Auth\Pages\EmailVerification\EmailVerificationPrompt
{
    protected function getVerifiable(): MustVerifyEmail
    {
        /** @var MustVerifyEmail $user */
        $user = Filament::auth()->user();

        return $user;
    }

    protected function sendEmailVerificationNotification(MustVerifyEmail $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        if (! method_exists($user, 'notify')) {
            $userClass = $user::class;

            throw new Exception("Model [{$userClass}] does not have a [notify()] method.");
        }

        $notification = app(VerifyEmail::class);
        $notification->url = Filament::getVerifyEmailUrl($user);

        $user->notify($notification);
    }
}
