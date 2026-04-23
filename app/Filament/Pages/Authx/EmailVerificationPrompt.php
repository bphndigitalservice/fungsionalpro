<?php

namespace App\Filament\Pages\Authx;


use Exception;
use Filament\Facades\Filament;
use Filament\Pages\Auth\EmailVerification\EmailVerificationPrompt as BaseEmailVerification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\Notifications\VerifyEmail;

class EmailVerificationPrompt extends BaseEmailVerification
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
