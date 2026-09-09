<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\VerifyApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class VerifyApiKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_missing_api_key(): void
    {
        $response = $this->getJson('/api/v1/ping');

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Unauthorized']);
    }

    public function test_it_rejects_invalid_api_key(): void
    {
        $response = $this->getJson('/api/v1/ping', [
            'X-Api-Key' => 'wrong-key',
        ]);

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Unauthorized']);
    }

    public function test_middleware_passes_valid_key(): void
    {
        $middleware = new VerifyApiKey();
        $request = Request::create('/api/v1/ping', 'GET');
        $request->headers->set('X-Api-Key', 'test-superapps-key');

        $response = $middleware->handle($request, fn () => new Response('ok', 200));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }
}
