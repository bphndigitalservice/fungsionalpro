<?php

namespace App\Providers;

use App\Policies\RolePolicy;
use App\Subscribers\ClientEventSubscriber;
use App\Subscribers\PointEventSubscriber;
use App\Subscribers\UserEventSubscriber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

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
        Model::unguard();

        /*Gate::policy(Role::class, RolePolicy::class);*/

        Event::subscribe(UserEventSubscriber::class);
        Event::subscribe(ClientEventSubscriber::class);
        Event::subscribe(PointEventSubscriber::class);
    }
}
