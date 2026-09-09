<?php

namespace Tests\Feature\Auth;

use App\Filament\Pages\Authx\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginErrorMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_email_shows_email_field_message(): void
    {
        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'missing@example.com',
                'password' => 'anything',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email' => __('auth.email_not_found')]);
    }

    public function test_wrong_password_shows_password_field_message(): void
    {
        User::factory()->create([
            'email' => 'known@example.com',
            'password' => 'correct-password',
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'known@example.com',
                'password' => 'wrong-password',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['password' => __('auth.password_incorrect')]);
    }
}
