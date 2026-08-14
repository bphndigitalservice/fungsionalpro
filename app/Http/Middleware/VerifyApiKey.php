<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured = (string) config('services.superapps.api_key', '');
        $provided = (string) $request->header('X-Api-Key', '');

        if ($configured === '' || ! hash_equals($configured, $provided)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
