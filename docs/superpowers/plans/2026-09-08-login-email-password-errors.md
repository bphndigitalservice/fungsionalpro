# Login Email vs Password Errors Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Show distinct login errors for unknown email vs wrong password on the matching form fields.

**Architecture:** Extend `App\Filament\Pages\Authx\Login::authenticate()` to resolve the user by email before `attempt()`, throw field-specific `ValidationException`s, keep panel-denial generic.

**Tech Stack:** Laravel 12, Filament v3 custom Login page, PHPUnit

## Global Constraints

- Spec: `docs/superpowers/specs/2026-09-08-login-email-password-errors-design.md`
- Copy: email → `Email tidak ditemukan.`; password → `Password yang Anda masukkan salah.`
- Keys: `auth.email_not_found`, `auth.password_incorrect` (do not overwrite Laravel `auth.password`)
- Rate limit 5 unchanged; panel-access denial stays generic
- **Do not git commit** — user commits manually

---

### Task 1: Distinct login failure messages

**Files:**
- Modify: `lang/id/auth.php`
- Modify: `app/Filament/Pages/Authx/Login.php`
- Create or modify: `tests/Feature/Auth/LoginErrorMessagesTest.php` (or similar under `tests/Feature`)

- [ ] **Step 1: Add lang keys**

In `lang/id/auth.php`:

```php
'email_not_found' => 'Email tidak ditemukan.',
'password_incorrect' => 'Password yang Anda masukkan salah.',
```

Keep existing `failed`, `password`, `throttle` keys.

- [ ] **Step 2: Write failing tests**

```php
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
            ->assertHasFormErrors(['email' => 'Email tidak ditemukan.']);
    }

    public function test_wrong_password_shows_password_field_message(): void
    {
        User::factory()->create([
            'email' => 'known@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'known@example.com',
                'password' => 'wrong-password',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['password' => 'Password yang Anda masukkan salah.']);
    }
}
```

Adjust field names if Filament uses `data.email` in assertions (`assertHasFormErrors(['data.email' => ...])` — match whatever existing Filament login tests / Filament v3 expect). Prefer asserting translation key via `__() ` if exact string matching is flaky.

- [ ] **Step 3: Run — expect FAIL**

```bash
php artisan test --filter=LoginErrorMessagesTest
```

- [ ] **Step 4: Implement `Login::authenticate` split**

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// After $data = $this->form->getState();

$user = User::query()->where('email', $data['email'])->first();

if ($user === null) {
    throw ValidationException::withMessages([
        'data.email' => __('auth.email_not_found'),
    ]);
}

if (! Filament::auth()->attempt([
    'email' => $data['email'],
    'password' => $data['password'],
], $data['remember'] ?? false)) {
    throw ValidationException::withMessages([
        'data.password' => __('auth.password_incorrect'),
    ]);
}

// existing FilamentUser panel check → throwFailureValidationException() (generic)
```

Remove or keep `throwFailureValidationException()` for the panel-denial path only.

- [ ] **Step 5: Run tests — PASS**

```bash
php artisan test --filter=LoginErrorMessagesTest
```

- [ ] **Step 6: Skip commit**
