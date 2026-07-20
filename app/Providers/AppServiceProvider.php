<?php

namespace App\Providers;

use App\Models\Client;
use App\Observers\ClientObserver;
use App\Subscribers\ClientEventSubscriber;
use App\Subscribers\PointEventSubscriber;
use App\Subscribers\UserEventSubscriber;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Auth\Http\Responses\RegistrationResponse;
use Filament\Facades\Filament;
use Filament\Pages\BasePage as Page;
use Filament\Resources\Resource;
use Filament\Widgets\Widget;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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

        Authenticate::redirectUsing(fn (): string => Filament::getLoginUrl());
        AuthenticateSession::redirectUsing(
            fn (): string => Filament::getLoginUrl()
        );
        AuthenticationException::redirectUsing(
            fn (): string => Filament::getLoginUrl()
        );

        // Filament resource forms mass-assign many attributes. Until every model
        // has an explicit $fillable whitelist, keep unguard enabled so saves don't
        // silently fail. Prefer adding $fillable per model, then remove this.
        // Sensitive columns are still stripped in mutateFormData* / forceFill paths.
        Model::unguard();
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
            if ($this->app->isProduction()) {
                return;
            }

            logger()->warning(sprintf(
                'Lazy loading [%s] on [%s:%s]',
                $relation,
                $model::class,
                $model->getKey(),
            ));
        });

        Event::subscribe(UserEventSubscriber::class);
        Event::subscribe(ClientEventSubscriber::class);
        Event::subscribe(PointEventSubscriber::class);

        Client::observe(ClientObserver::class);

        FilamentShield::buildPermissionKeyUsing(
            function (string $entity, string $affix, string $subject, string $case, string $separator) {
                return match (true) {
                    is_subclass_of($entity, Resource::class) => Str::of($affix)
                        ->snake()
                        ->append('_')
                        ->append(
                            Str::of($entity)
                                ->afterLast('\\')
                                ->beforeLast('Resource')
                                ->replace('\\', '')
                                ->snake()
                                ->replace('_', '::')
                        )
                        ->toString(),
                    is_subclass_of($entity, Page::class) => Str::of('page_')
                        ->append(class_basename($entity))
                        ->toString(),
                    is_subclass_of($entity, Widget::class) => Str::of('widget_')
                        ->append(class_basename($entity))
                        ->toString(),
                    default => Str::of($affix)
                        ->snake()
                        ->append($separator)
                        ->append(Str::of($subject)->snake())
                        ->toString(),
                };
            }
        );

        FilamentShield::prohibitDestructiveCommands($this->app->isProduction());
    }
}
