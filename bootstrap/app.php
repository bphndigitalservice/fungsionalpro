<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Request as Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $trustedProxy = env('TRUSTED_PROXY');

        $middleware->trustProxies(
            at: match (true) {
                $trustedProxy === null, $trustedProxy === '' => null,
                $trustedProxy === '*' => '*',
                default => array_values(array_filter(array_map('trim', explode(',', $trustedProxy)))),
            },
            headers: Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO |
                Request::HEADER_X_FORWARDED_AWS_ELB
        );

        $middleware->alias([
            'auth' => \Filament\Http\Middleware\Authenticate::class,
            'verify.api.key' => \App\Http\Middleware\VerifyApiKey::class,
        ]);

        $middleware->append(\App\Http\Middleware\SetSecurityHeaders::class);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
