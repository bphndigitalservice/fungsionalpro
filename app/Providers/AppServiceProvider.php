<?php

namespace App\Providers;

use App\Subscribers\ClientEventSubscriber;
use App\Subscribers\PointEventSubscriber;
use App\Subscribers\UserEventSubscriber;
use BezhanSalleh\FilamentShield\FilamentShield;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\RegistrationResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        if (app()->environment('production', 'staging')) {
            URL::forceScheme('https');
        }

        $this->app->bind(RegistrationResponse::class, \App\Filament\Pages\Authx\RegistrationResponse::class);

        Authenticate::redirectUsing(fn(): string => Filament::getLoginUrl());
        AuthenticateSession::redirectUsing(
            fn(): string => Filament::getLoginUrl()
        );
        AuthenticationException::redirectUsing(
            fn(): string => Filament::getLoginUrl()
        );


        Model::unguard();


        Event::subscribe(UserEventSubscriber::class);
        Event::subscribe(ClientEventSubscriber::class);
        Event::subscribe(PointEventSubscriber::class);


        FilamentShield::prohibitDestructiveCommands($this->app->isProduction());
    }
}
