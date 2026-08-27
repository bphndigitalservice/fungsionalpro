# Angka Kredit Verification Gate Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Hide and block Angka Kredit for unverified client-without-SuperAdmin users (redirect to Dashboard), and show a persistent Dashboard notice with the exact verify-identity copy.

**Architecture:** Reuse `ClientMenuAccess` verification helpers. Gate `ClientPointList` / `Create` / `Edit` with verified AND photo. Add a Dashboard-targeted redirect trait sibling to `RedirectsLockedClientMenuAccess`. Replace the photo-only Dashboard notice for unverified clients with the new persistent message.

**Tech Stack:** Laravel 12, Filament v3, Livewire 3, PHPUnit 11, `RefreshDatabase`

## Global Constraints

- Spec: `docs/superpowers/specs/2026-08-26-angka-kredit-verification-gate-design.md`
- Unlock only when `Client.is_verified === Verified::Verified`
- Client-without-SuperAdmin only; Rule-set unchanged (SuperAdmin); do not grant Rule-set to clients
- Redirect locked Angka Kredit URLs to **Dashboard**, not Identitas
- Dashboard notice exact ID copy: `Lengkapi Identitas dan Tunggu Identitas anda di verifikasi` via lang key; `->persistent()`; replaces photo-only notice for unverified clients
- Keep existing photo AND with verification on point pages
- Do not change Profil Saya Identitas redirect behavior
- Conventional commits; PHPUnit; `forceFill` for guarded `is_verified`
- Git identity via env vars only (no `git config`): Frans Filasta Pratama / fransfilastap@live.com

---

## File structure

| File | Responsibility |
| --- | --- |
| `lang/id/labels.php`, `lang/en/labels.php` | `page.dashboard.verify_identity_required` |
| `app/Concerns/Filament/RedirectsLockedAngkaKreditAccess.php` | Redirect locked clients to Dashboard + notice |
| `app/Filament/Pages/Client/Point/ClientPointList.php` | Verified+photo nav/access; use redirect trait |
| `app/Filament/Pages/Client/Point/ClientPointCreate.php` | Same |
| `app/Filament/Pages/Client/Point/ClientPointEdit.php` | Verified+photo access; use redirect trait |
| `app/Filament/Pages/Dashboard.php` | Persistent verify notice; drop photo notice for unverified |
| `tests/Feature/Filament/AngkaKreditVerificationGateTest.php` | Nav, redirect, dashboard notice tests |

---

### Task 1: Lang keys + Dashboard persistent notice

**Files:**
- Modify: `lang/id/labels.php`, `lang/en/labels.php`, `app/Filament/Pages/Dashboard.php`
- Create: `tests/Feature/Filament/AngkaKreditVerificationGateTest.php` (dashboard cases)

**Interfaces:**
- Produces lang key `labels.page.dashboard.verify_identity_required`
- Dashboard `mount()` uses `ClientMenuAccess` + `Client::current()` / user client

- [ ] **Step 1: Write failing Dashboard notice tests**

Reuse `actingAsClient` pattern from `ClientMenuVerificationGateTest` (photo via `$client->identity()->update`).

```php
public function test_unverified_client_gets_persistent_verify_notice_on_dashboard(): void
{
    $this->actingAsClient(Verified::Unverified);

    Livewire::test(\App\Filament\Pages\Dashboard::class)
        ->assertSuccessful();

    // Assert notification was sent — use Filament's Notification::assert* if available in this Filament version,
    // or assert session database notifications / Livewire notification bag used by the project.
    // Minimum: mount does not throw and lang key resolves to the exact ID string.
    $this->assertSame(
        'Lengkapi Identitas dan Tunggu Identitas anda di verifikasi',
        __('labels.page.dashboard.verify_identity_required')
    );
}
```

Also assert: when verified, the old photo-only body string is not the path used for unverified (and verified client without photo does not get the verify notice).

If Filament notification assertions are awkward, assert a new protected/testable method on Dashboard e.g. `shouldSendVerifyIdentityNotice(): bool` and that `mount` calls the persistent notification when true — prefer behavior over private internals when possible.

- [ ] **Step 2: Run test — expect FAIL** (missing lang key / still photo notice)

- [ ] **Step 3: Add lang keys + update Dashboard**

`lang/id/labels.php` under `page`:

```php
'dashboard' => [
    'verify_identity_required' => 'Lengkapi Identitas dan Tunggu Identitas anda di verifikasi',
],
```

`lang/en/labels.php` equivalent English.

`Dashboard::mount()`:

```php
if ($user && ClientMenuAccess::isClientWithoutSuperAdmin($user)) {
    $client = Client::current() ?? Client::where('user_id', $user->id)->first();
    if ($client && ! ClientMenuAccess::clientProfileIsVerified($client)) {
        Notification::make()
            ->title(__('labels.page.dashboard.verify_identity_required'))
            ->warning()
            ->persistent()
            ->send();
        return;
    }
}
```

Remove the old photo-only persistent notice branch (or leave it unreachable for unverified; do not send both).

- [ ] **Step 4: Tests pass + commit**

```bash
git commit -m "feat(filament): show persistent verify notice on dashboard for unverified clients"
```

---

### Task 2: Gate Angka Kredit pages + redirect to Dashboard

**Files:**
- Create: `app/Concerns/Filament/RedirectsLockedAngkaKreditAccess.php`
- Modify: `ClientPointList.php`, `ClientPointCreate.php`, `ClientPointEdit.php`
- Modify: `tests/Feature/Filament/AngkaKreditVerificationGateTest.php`

**Interfaces:**
- Consumes: `ClientMenuAccess::clientMayAccessVerifiedClientMenuItem()`, photo check via `Client::current()->identity?->photo`
- Produces: `shouldRegisterNavigation` / mount access requiring both; redirect to `Dashboard::getUrl()` with warning title from `labels.page.dashboard.verify_identity_required`

- [ ] **Step 1: Write failing tests**

```php
public function test_unverified_client_cannot_register_angka_kredit_nav(): void
{
    $this->actingAsClient(Verified::Unverified, withPhoto: true);
    $this->assertFalse(ClientPointList::shouldRegisterNavigation());
    $this->assertFalse(ClientPointCreate::shouldRegisterNavigation());
}

public function test_verified_client_with_photo_can_register_angka_kredit_nav(): void
{
    $this->actingAsClient(Verified::Verified, withPhoto: true);
    $this->assertTrue(ClientPointList::shouldRegisterNavigation());
    $this->assertTrue(ClientPointCreate::shouldRegisterNavigation());
}

public function test_verified_client_without_photo_cannot_register_angka_kredit_nav(): void
{
    $this->actingAsClient(Verified::Verified, withPhoto: false);
    $this->assertFalse(ClientPointList::shouldRegisterNavigation());
}

public function test_unverified_client_is_redirected_from_point_list_to_dashboard(): void
{
    $this->actingAsClient(Verified::Unverified, withPhoto: true);
    Livewire::test(ClientPointList::class)
        ->assertRedirect(Dashboard::getUrl());
}
```

- [ ] **Step 2: Run — expect FAIL**

- [ ] **Step 3: Implement trait + wire pages**

Trait mirrors `RedirectsLockedClientMenuAccess` but:
- Target `Dashboard::getUrl()`
- Title `__('labels.page.dashboard.verify_identity_required')`
- Override `mountCanAuthorizeAccess` / hydrate similarly for Page (not Resource)

On List/Create/Edit:
- Helper method e.g. `clientMayAccessAngkaKredit(): bool` = `ClientMenuAccess::clientMayAccessVerifiedClientMenuItem() && photo present` (for client-without-superadmin; non-clients keep prior `return true` when no client)
- `shouldRegisterNavigation` uses that (Edit stays `false` for nav)
- Replace bare `abort(403)` photo checks with redirect trait path for client-without-superadmin when locked
- `use RedirectsLockedAngkaKreditAccess`

For pages using `HasPageShield`, compose carefully so verification+photo gate runs for nav and mount.

- [ ] **Step 4: Tests pass + commit**

```bash
git commit -m "feat(filament): gate angka kredit on verification and redirect to dashboard"
```

---

### Task 3: Suite sanity

- [ ] **Step 1:** Run `php artisan test --filter=AngkaKreditVerificationGateTest` and `php artisan test --filter=ClientMenuVerificationGateTest`
- [ ] **Step 2:** Confirm Profil Saya redirects still go to Identitas (existing tests)
- [ ] **Step 3:** Commit only if fixes needed

---

## Self-review vs spec

| Spec item | Task |
|---|---|
| Hide/block Angka Kredit until Verified | Task 2 |
| Redirect to Dashboard | Task 2 |
| Persistent notice exact copy; replaces photo notice | Task 1 |
| Photo AND | Task 2 |
| Rule-set / Profil Saya unchanged | Tasks 2–3 |
