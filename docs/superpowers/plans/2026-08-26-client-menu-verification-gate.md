# Client Menu Verification Gate Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Restrict Profil Saya (`labels.nav.client_menu`) so client-only users see Identitas always, see Kegiatan/Diklat/Pendidikan only when `clients.is_verified` is Verified, and never see SuperAdmin-only Profil Saya items; locked URLs redirect to Identitas with a notice.

**Architecture:** Shared Filament concerns under `app/Concerns/Filament/`, modeled on `ChecksPhotoUpload`. Helpers decide role + verification; composite traits drive `shouldRegisterNavigation` / `canAccess` / `canViewAny` / `canCreate`; resource/page mount hooks redirect locked client-only users to `ClientProfilePage` instead of a bare 403.

**Tech Stack:** Laravel 12, Filament v3, Livewire 3, Spatie Permission (`SystemRole`), PHPUnit 11, `RefreshDatabase`, SQLite in-memory tests

## Global Constraints

- Spec: `docs/superpowers/specs/2026-08-26-client-menu-verification-gate-design.md`
- Unlock verified menus only when `Client.is_verified === Verified::Verified` (Unverified and Rejected stay locked)
- Client allowlist: Identitas, Riwayat Kegiatan, Diklat/Pelatihan, Riwayat Pendidikan
- SuperAdmin-only under Profil Saya: Riwayat Jabatan, Riwayat Pangkat/Golongan, Informasi Pendukung, Informasi Dasar
- Identitas (`ClientProfilePage`) is exempt from the verification lock
- Stack with existing `ChecksPhotoUpload` (AND) on Kegiatan / Diklat / Pendidikan
- `is_verified` is `$guarded` on `Client` — use `forceFill` / `verified()` in tests, never mass-assign
- Conventional commits; commit after each task
- PHPUnit (not Pest) for Filament tests; follow `tests/Feature/Filament/AdminAccessFormTest.php`

---

## File structure

| File | Responsibility |
| --- | --- |
| `app/Concerns/Filament/ClientMenuAccess.php` | Static helpers: client-without-superadmin, verified?, redirect decision |
| `app/Concerns/Filament/GatesVerifiedClientOwnRecords.php` | Replaces `ChecksPhotoUpload` on allowlist resources: photo AND verified for client-only |
| `app/Concerns/Filament/RequiresSuperAdminForClientMenu.php` | Nav + access only for SuperAdmin |
| `app/Concerns/Filament/RedirectsLockedClientMenuAccess.php` | Livewire mount hooks: notice + redirect to Identitas |
| `lang/id/labels.php` | Notice / copy keys under `page.client_profile` |
| `lang/en/labels.php` | English equivalents |
| `app/Filament/Resources/ClientActivityResource.php` | Use `GatesVerifiedClientOwnRecords` |
| `app/Filament/Resources/ClientCompetenceResource.php` | Use `GatesVerifiedClientOwnRecords` |
| `app/Filament/Resources/ClientEducationResource.php` | Use `GatesVerifiedClientOwnRecords` |
| `app/Filament/Resources/ClientPositionResource.php` | Use SuperAdmin gate |
| `app/Filament/Resources/ClientGradeResource.php` | Use SuperAdmin gate |
| `app/Filament/Resources/ClientDossierResource.php` | Use SuperAdmin gate |
| `app/Filament/Pages/Client/ClientBasicIdentityPage.php` | Use SuperAdmin gate + redirect |
| Resource `Pages/*` under locked resources | Use redirect trait |
| `tests/Feature/Filament/ClientMenuAccessHelpersTest.php` | Helper coverage |
| `tests/Feature/Filament/ClientMenuVerificationGateTest.php` | Nav + access + redirect coverage |

**Do not modify:** `ClientProfilePage` verification exemption logic beyond leaving it ungated; verifier workflows; other nav groups.

---

### Task 1: ClientMenuAccess helpers + failing tests

**Files:**
- Create: `app/Concerns/Filament/ClientMenuAccess.php`
- Create: `tests/Feature/Filament/ClientMenuAccessHelpersTest.php`

**Interfaces:**
- Consumes: `App\Models\User`, `App\Models\Client`, `App\Enums\SystemRole`, `App\Enums\Verified`
- Produces:
  - `ClientMenuAccess::isClientWithoutSuperAdmin(?User $user = null): bool`
  - `ClientMenuAccess::clientProfileIsVerified(?Client $client = null): bool`
  - `ClientMenuAccess::clientMayAccessVerifiedClientMenuItem(?User $user = null, ?Client $client = null): bool`
  - `ClientMenuAccess::userMayAccessSuperAdminClientMenuItem(?User $user = null): bool`

- [ ] **Step 1: Write the failing helper tests**

Create `tests/Feature/Filament/ClientMenuAccessHelpersTest.php`:

```php
<?php

namespace Tests\Feature\Filament;

use App\Concerns\Filament\ClientMenuAccess;
use App\Enums\ClientCluster;
use App\Enums\SystemRole;
use App\Enums\Verified;
use App\Models\Client;
use App\Models\CRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClientMenuAccessHelpersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate(SystemRole::Client->value, 'web');
        Role::findOrCreate(SystemRole::SuperAdmin->value, 'web');
        Role::findOrCreate(SystemRole::Admin->value, 'web');
    }

    protected function makeClientUser(Verified $verified = Verified::Unverified): array
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::Client->value);

        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);

        $client = Client::create([
            'user_id' => $user->id,
            'c_role_id' => $role->id,
            'nip' => '199001012020121001',
            'type' => ClientCluster::Central,
            'agency_type' => 'department',
            'agency_id' => 1,
        ]);

        $client->forceFill([
            'is_verified' => $verified,
            'verified_at' => $verified === Verified::Verified ? now() : null,
        ])->save();

        return [$user->fresh(), $client->fresh()];
    }

    public function test_client_without_superadmin_is_detected(): void
    {
        [$user] = $this->makeClientUser();

        $this->assertTrue(ClientMenuAccess::isClientWithoutSuperAdmin($user));
    }

    public function test_client_who_is_also_superadmin_is_not_client_without_superadmin(): void
    {
        [$user] = $this->makeClientUser();
        $user->assignRole(SystemRole::SuperAdmin->value);

        $this->assertFalse(ClientMenuAccess::isClientWithoutSuperAdmin($user->fresh()));
    }

    public function test_unverified_and_rejected_are_not_verified(): void
    {
        [, $unverified] = $this->makeClientUser(Verified::Unverified);
        [, $rejected] = $this->makeClientUser(Verified::Rejected);

        $this->assertFalse(ClientMenuAccess::clientProfileIsVerified($unverified));
        $this->assertFalse(ClientMenuAccess::clientProfileIsVerified($rejected));
    }

    public function test_verified_client_may_access_verified_menu_items(): void
    {
        [$user, $client] = $this->makeClientUser(Verified::Verified);

        $this->assertTrue(ClientMenuAccess::clientMayAccessVerifiedClientMenuItem($user, $client));
    }

    public function test_unverified_client_may_not_access_verified_menu_items(): void
    {
        [$user, $client] = $this->makeClientUser(Verified::Unverified);

        $this->assertFalse(ClientMenuAccess::clientMayAccessVerifiedClientMenuItem($user, $client));
    }

    public function test_non_client_is_not_restricted_by_verified_menu_helper(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(SystemRole::Admin->value);

        $this->assertTrue(ClientMenuAccess::clientMayAccessVerifiedClientMenuItem($admin, null));
    }

    public function test_only_superadmin_may_access_superadmin_client_menu_items(): void
    {
        [$clientUser] = $this->makeClientUser();
        $super = User::factory()->create();
        $super->assignRole(SystemRole::SuperAdmin->value);

        $this->assertFalse(ClientMenuAccess::userMayAccessSuperAdminClientMenuItem($clientUser));
        $this->assertTrue(ClientMenuAccess::userMayAccessSuperAdminClientMenuItem($super));
    }
}
```

If `Client::create` fails on required columns in this environment, adjust the factory helper to satisfy NOT NULL columns only (do not invent unrelated schema). Prefer the smallest set that migrates cleanly.

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=ClientMenuAccessHelpersTest`

Expected: FAIL (class `ClientMenuAccess` not found)

- [ ] **Step 3: Implement helpers**

Create `app/Concerns/Filament/ClientMenuAccess.php`:

```php
<?php

namespace App\Concerns\Filament;

use App\Enums\SystemRole;
use App\Enums\Verified;
use App\Models\Client;
use App\Models\User;

final class ClientMenuAccess
{
    public static function isClientWithoutSuperAdmin(?User $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->hasSystemRole(SystemRole::Client) && ! $user->isSuperAdmin();
    }

    public static function clientProfileIsVerified(?Client $client = null): bool
    {
        $client ??= Client::current();

        return $client !== null && $client->is_verified === Verified::Verified;
    }

    public static function clientMayAccessVerifiedClientMenuItem(?User $user = null, ?Client $client = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        if (! static::isClientWithoutSuperAdmin($user)) {
            return true;
        }

        return static::clientProfileIsVerified($client ?? $user->client);
    }

    public static function userMayAccessSuperAdminClientMenuItem(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user !== null && $user->isSuperAdmin();
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=ClientMenuAccessHelpersTest`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Concerns/Filament/ClientMenuAccess.php tests/Feature/Filament/ClientMenuAccessHelpersTest.php
git commit -m "feat(filament): add client menu access helpers"
```

---

### Task 2: GatesVerifiedClientOwnRecords + wire allowlist resources

**Files:**
- Create: `app/Concerns/Filament/GatesVerifiedClientOwnRecords.php`
- Modify: `app/Filament/Resources/ClientActivityResource.php` (replace `use ChecksPhotoUpload`)
- Modify: `app/Filament/Resources/ClientCompetenceResource.php`
- Modify: `app/Filament/Resources/ClientEducationResource.php`
- Create: `tests/Feature/Filament/ClientMenuVerificationGateTest.php` (nav/access cases for allowlist)

**Interfaces:**
- Consumes: `ClientMenuAccess`, `ChecksPhotoUpload` (aliased private methods)
- Produces: resource `shouldRegisterNavigation` / `canAccess` / `canViewAny` / `canCreate` requiring photo AND verified for client-only users; ownership methods unchanged from `ChecksPhotoUpload`

- [ ] **Step 1: Write failing resource gate tests**

Add to `tests/Feature/Filament/ClientMenuVerificationGateTest.php`:

```php
<?php

namespace Tests\Feature\Filament;

use App\Enums\ClientCluster;
use App\Enums\SystemRole;
use App\Enums\Verified;
use App\Filament\Resources\ClientActivityResource;
use App\Filament\Resources\ClientCompetenceResource;
use App\Filament\Resources\ClientEducationResource;
use App\Models\Client;
use App\Models\ClientIdentity;
use App\Models\CRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClientMenuVerificationGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate(SystemRole::Client->value, 'web');
        Role::findOrCreate(SystemRole::SuperAdmin->value, 'web');
    }

    protected function actingAsClient(Verified $verified, bool $withPhoto = true): User
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::Client->value);

        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);

        $client = Client::create([
            'user_id' => $user->id,
            'c_role_id' => $role->id,
            'nip' => fake()->unique()->numerify('##################'),
            'type' => ClientCluster::Central,
            'agency_type' => 'department',
            'agency_id' => 1,
        ]);

        $client->forceFill([
            'is_verified' => $verified,
            'verified_at' => $verified === Verified::Verified ? now() : null,
        ])->save();

        if ($withPhoto) {
            ClientIdentity::create([
                'client_id' => $client->id,
                'photo' => 'clients/photo.jpg',
            ]);
        }

        $this->actingAs($user->fresh());

        return $user->fresh();
    }

    public function test_unverified_client_cannot_register_verified_allowlist_nav(): void
    {
        $this->actingAsClient(Verified::Unverified);

        $this->assertFalse(ClientActivityResource::shouldRegisterNavigation());
        $this->assertFalse(ClientCompetenceResource::shouldRegisterNavigation());
        $this->assertFalse(ClientEducationResource::shouldRegisterNavigation());
    }

    public function test_verified_client_with_photo_can_register_allowlist_nav(): void
    {
        $this->actingAsClient(Verified::Verified, withPhoto: true);

        $this->assertTrue(ClientActivityResource::shouldRegisterNavigation());
        $this->assertTrue(ClientCompetenceResource::shouldRegisterNavigation());
        $this->assertTrue(ClientEducationResource::shouldRegisterNavigation());
    }

    public function test_verified_client_without_photo_still_blocked_by_photo_gate(): void
    {
        $this->actingAsClient(Verified::Verified, withPhoto: false);

        $this->assertFalse(ClientActivityResource::shouldRegisterNavigation());
        $this->assertFalse(ClientActivityResource::canAccess());
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=ClientMenuVerificationGateTest`

Expected: FAIL (unverified client still gets `true` from photo-only gate, or trait missing)

- [ ] **Step 3: Implement composite trait**

Create `app/Concerns/Filament/GatesVerifiedClientOwnRecords.php`:

```php
<?php

namespace App\Concerns\Filament;

use Illuminate\Database\Eloquent\Model;

trait GatesVerifiedClientOwnRecords
{
    use ChecksPhotoUpload {
        shouldRegisterNavigation as private checksPhotoShouldRegisterNavigation;
        canAccess as private checksPhotoCanAccess;
        canViewAny as private checksPhotoCanViewAny;
        canCreate as private checksPhotoCanCreate;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return ClientMenuAccess::clientMayAccessVerifiedClientMenuItem()
            && static::checksPhotoShouldRegisterNavigation();
    }

    public static function canAccess(): bool
    {
        return ClientMenuAccess::clientMayAccessVerifiedClientMenuItem()
            && static::checksPhotoCanAccess();
    }

    public static function canViewAny(): bool
    {
        return ClientMenuAccess::clientMayAccessVerifiedClientMenuItem()
            && static::checksPhotoCanViewAny();
    }

    public static function canCreate(): bool
    {
        return ClientMenuAccess::clientMayAccessVerifiedClientMenuItem()
            && static::checksPhotoCanCreate();
    }

    // canView / canEdit / canDelete remain from ChecksPhotoUpload (ownership)
}
```

In `ClientActivityResource`, `ClientCompetenceResource`, and `ClientEducationResource`:

- Change `use App\Concerns\Filament\ChecksPhotoUpload;` → `use App\Concerns\Filament\GatesVerifiedClientOwnRecords;`
- Change `use ChecksPhotoUpload;` → `use GatesVerifiedClientOwnRecords;`

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=ClientMenuVerificationGateTest`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Concerns/Filament/GatesVerifiedClientOwnRecords.php app/Filament/Resources/ClientActivityResource.php app/Filament/Resources/ClientCompetenceResource.php app/Filament/Resources/ClientEducationResource.php tests/Feature/Filament/ClientMenuVerificationGateTest.php
git commit -m "feat(filament): gate client allowlist menus on verification"
```

---

### Task 3: SuperAdmin-only Profil Saya items

**Files:**
- Create: `app/Concerns/Filament/RequiresSuperAdminForClientMenu.php`
- Modify: `app/Filament/Resources/ClientPositionResource.php`
- Modify: `app/Filament/Resources/ClientGradeResource.php`
- Modify: `app/Filament/Resources/ClientDossierResource.php`
- Modify: `app/Filament/Pages/Client/ClientBasicIdentityPage.php`
- Modify: `tests/Feature/Filament/ClientMenuVerificationGateTest.php`

**Interfaces:**
- Consumes: `ClientMenuAccess::userMayAccessSuperAdminClientMenuItem()`
- Produces: `shouldRegisterNavigation(): bool`, `canAccess(): bool`, `canViewAny(): bool` (resources); page `canAccess` / `shouldRegisterNavigation` for `ClientBasicIdentityPage`

- [ ] **Step 1: Write failing SuperAdmin-only tests**

Append to `ClientMenuVerificationGateTest`:

```php
use App\Filament\Pages\Client\ClientBasicIdentityPage;
use App\Filament\Resources\ClientDossierResource;
use App\Filament\Resources\ClientGradeResource;
use App\Filament\Resources\ClientPositionResource;

public function test_client_only_cannot_see_superadmin_client_menu_items(): void
{
    $this->actingAsClient(Verified::Verified);

    $this->assertFalse(ClientPositionResource::shouldRegisterNavigation());
    $this->assertFalse(ClientGradeResource::shouldRegisterNavigation());
    $this->assertFalse(ClientDossierResource::shouldRegisterNavigation());
    $this->assertFalse(ClientBasicIdentityPage::shouldRegisterNavigation());
    $this->assertFalse(ClientPositionResource::canAccess());
}

public function test_superadmin_can_see_superadmin_client_menu_items(): void
{
    $super = User::factory()->create();
    $super->assignRole(SystemRole::SuperAdmin->value);
    $this->actingAs($super);

    $this->assertTrue(ClientPositionResource::shouldRegisterNavigation());
    $this->assertTrue(ClientGradeResource::shouldRegisterNavigation());
    $this->assertTrue(ClientDossierResource::shouldRegisterNavigation());
    $this->assertTrue(ClientBasicIdentityPage::shouldRegisterNavigation());
}
```

Note: if `ClientGradeResource` / `ClientDossierResource` previously required `Client::current() !== null`, SuperAdmin without a client record should still pass under this feature (SuperAdmin gate only). Do **not** keep the `Client::current() !== null` nav check for these SuperAdmin-only items unless product confirms otherwise — the approved spec is SuperAdmin-only.

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=ClientMenuVerificationGateTest`

Expected: FAIL on SuperAdmin-only assertions

- [ ] **Step 3: Implement trait and wire resources/page**

Create `app/Concerns/Filament/RequiresSuperAdminForClientMenu.php`:

```php
<?php

namespace App\Concerns\Filament;

trait RequiresSuperAdminForClientMenu
{
    public static function shouldRegisterNavigation(): bool
    {
        return ClientMenuAccess::userMayAccessSuperAdminClientMenuItem();
    }

    public static function canAccess(): bool
    {
        return ClientMenuAccess::userMayAccessSuperAdminClientMenuItem();
    }

    public static function canViewAny(): bool
    {
        return ClientMenuAccess::userMayAccessSuperAdminClientMenuItem();
    }
}
```

Wire:

- `ClientPositionResource`: `use RequiresSuperAdminForClientMenu;`
- `ClientGradeResource`: `use RequiresSuperAdminForClientMenu;` and **remove** the existing `shouldRegisterNavigation()` method that checks `Client::current()`
- `ClientDossierResource`: same as grade
- `ClientBasicIdentityPage`: `use RequiresSuperAdminForClientMenu;` (Filament pages already support `shouldRegisterNavigation` / `canAccess` via panel page APIs — if the page inherits Shield `canAccess`, compose with `insteadof` / explicit override so SuperAdmin gate wins for registration and access)

For `ClientBasicIdentityPage`, if `HasPageShield` defines `canAccess` / `shouldRegisterNavigation`, resolve conflict explicitly:

```php
use RequiresSuperAdminForClientMenu {
    canAccess as protected superAdminCanAccess;
    shouldRegisterNavigation as protected superAdminShouldRegisterNavigation;
}

public static function canAccess(): bool
{
    return static::superAdminCanAccess();
}

public static function shouldRegisterNavigation(): bool
{
    return static::superAdminShouldRegisterNavigation();
}
```

(Only if Shield conflict appears; otherwise a single `use RequiresSuperAdminForClientMenu` is enough.)

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=ClientMenuVerificationGateTest`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Concerns/Filament/RequiresSuperAdminForClientMenu.php app/Filament/Resources/ClientPositionResource.php app/Filament/Resources/ClientGradeResource.php app/Filament/Resources/ClientDossierResource.php app/Filament/Pages/Client/ClientBasicIdentityPage.php tests/Feature/Filament/ClientMenuVerificationGateTest.php
git commit -m "feat(filament): restrict extra profil saya menus to superadmin"
```

---

### Task 4: Redirect locked clients to Identitas + notice

**Files:**
- Create: `app/Concerns/Filament/RedirectsLockedClientMenuAccess.php`
- Modify: lang files `lang/id/labels.php`, `lang/en/labels.php`
- Modify: all List/Create/Edit/View pages under:
  - `ClientActivityResource/Pages`
  - `ClientCompetenceResource/Pages`
  - `ClientEducationResource/Pages`
  - `ClientPositionResource/Pages`
  - `ClientGradeResource/Pages`
  - `ClientDossierResource/Pages`
- Modify: `ClientBasicIdentityPage` (override page `mountCanAuthorizeAccess` or equivalent)
- Modify: `tests/Feature/Filament/ClientMenuVerificationGateTest.php`

**Interfaces:**
- Consumes: `ClientMenuAccess`, `ClientProfilePage::getUrl()`, Filament `Notification`
- Produces: instance methods that intercept authorization mount hooks and `$this->redirect(...)` when the current user is client-without-superadmin and the resource/page `canAccess` (or SuperAdmin gate) is false

- [ ] **Step 1: Add lang keys**

In `lang/id/labels.php` under `page.client_profile`:

```php
'verify_required' => 'Lengkapi dan verifikasi Identitas terlebih dahulu',
```

In `lang/en/labels.php` under the matching `page.client_profile` array (create the array if missing):

```php
'verify_required' => 'Please complete and verify your Identitas first',
```

- [ ] **Step 2: Write failing redirect test**

Append:

```php
use App\Filament\Pages\Client\ClientProfilePage;
use App\Filament\Resources\ClientActivityResource\Pages\ListClientActivities;
use App\Filament\Resources\ClientPositionResource\Pages\ListClientPositions;
use Livewire\Livewire;

public function test_unverified_client_is_redirected_from_activity_list_to_identitas(): void
{
    $this->actingAsClient(Verified::Unverified);

    Livewire::test(ListClientActivities::class)
        ->assertRedirect(ClientProfilePage::getUrl());
}

public function test_client_is_redirected_from_position_list_to_identitas(): void
{
    $this->actingAsClient(Verified::Verified);

    Livewire::test(ListClientPositions::class)
        ->assertRedirect(ClientProfilePage::getUrl());
}
```

If Livewire asserts 403 before redirect without the trait, that is the expected failure mode before Step 4.

- [ ] **Step 3: Run test to verify it fails**

Run: `php artisan test --filter=test_unverified_client_is_redirected_from_activity_list_to_identitas`

Expected: FAIL (403 or no redirect)

- [ ] **Step 4: Implement redirect trait and wire pages**

Create `app/Concerns/Filament/RedirectsLockedClientMenuAccess.php`:

```php
<?php

namespace App\Concerns\Filament;

use App\Filament\Pages\Client\ClientProfilePage;
use Filament\Notifications\Notification;

trait RedirectsLockedClientMenuAccess
{
    public function mountCanAuthorizeResourceAccess(): void
    {
        if ($this->redirectIfClientMenuLocked(static::getResource()::canAccess())) {
            return;
        }

        abort_unless(static::getResource()::canAccess(), 403);
    }

    protected function authorizeAccess(): void
    {
        if (method_exists(static::class, 'getResource')) {
            $allowed = static::getResource()::canCreate();
        } else {
            $allowed = static::canAccess();
        }

        if ($this->redirectIfClientMenuLocked($allowed)) {
            return;
        }

        abort_unless($allowed, 403);
    }

    public function mountCanAuthorizeAccess(): void
    {
        $allowed = method_exists(static::class, 'getResource')
            ? static::canAccess(['record' => $this->getRecord()])
            : static::canAccess();

        if ($this->redirectIfClientMenuLocked($allowed)) {
            return;
        }

        abort_unless($allowed, 403);
    }

    protected function redirectIfClientMenuLocked(bool $allowed): bool
    {
        if ($allowed || ! ClientMenuAccess::isClientWithoutSuperAdmin()) {
            return false;
        }

        Notification::make()
            ->warning()
            ->title(__('labels.page.client_profile.verify_required'))
            ->send();

        $this->redirect(ClientProfilePage::getUrl());

        return true;
    }
}
```

Wire `use RedirectsLockedClientMenuAccess;` on each locked resource page class listed above, and on `ClientBasicIdentityPage` (for page-level `mountCanAuthorizeAccess`).

If a page already defines `authorizeAccess` / `mountCanAuthorizeAccess`, remove duplicates and keep the trait version, or alias carefully so the redirect path runs first.

- [ ] **Step 5: Run redirect tests**

Run: `php artisan test --filter=ClientMenuVerificationGateTest`

Expected: PASS (including redirects)

If notification assertion is desired, optionally assert session/Filament notification presence using the project’s existing notification test style; title key is enough if redirect is proven.

- [ ] **Step 6: Commit**

```bash
git add app/Concerns/Filament/RedirectsLockedClientMenuAccess.php lang/id/labels.php lang/en/labels.php app/Filament/Resources/ClientActivityResource/Pages app/Filament/Resources/ClientCompetenceResource/Pages app/Filament/Resources/ClientEducationResource/Pages app/Filament/Resources/ClientPositionResource/Pages app/Filament/Resources/ClientGradeResource/Pages app/Filament/Resources/ClientDossierResource/Pages app/Filament/Pages/Client/ClientBasicIdentityPage.php tests/Feature/Filament/ClientMenuVerificationGateTest.php
git commit -m "feat(filament): redirect locked clients to identitas"
```

---

### Task 5: Full suite sanity + Identitas exemption check

**Files:**
- Modify: `tests/Feature/Filament/ClientMenuVerificationGateTest.php` only if needed
- No production changes unless a regression is found

- [ ] **Step 1: Add Identitas exemption assertion**

```php
use App\Filament\Pages\Client\ClientProfilePage;

public function test_unverified_client_can_still_access_identitas_navigation(): void
{
    $this->actingAsClient(Verified::Unverified);

    $this->assertTrue(ClientProfilePage::shouldRegisterNavigation());
}
```

If `ClientProfilePage::shouldRegisterNavigation` depends on Shield permissions, assign the page permission to the client role in `setUp` / helper (match how other Filament page tests grant Shield permissions in this repo). Do not weaken Identitas auth for SuperAdmin/admin.

- [ ] **Step 2: Run focused + related suites**

Run:

```bash
php artisan test --filter=ClientMenuAccessHelpersTest
php artisan test --filter=ClientMenuVerificationGateTest
```

Expected: PASS

- [ ] **Step 3: Commit if test-only changes**

```bash
git add tests/Feature/Filament/ClientMenuVerificationGateTest.php
git commit -m "test(filament): assert identitas stays available when unverified"
```

(Skip commit if no file changes.)

---

## Self-review (plan vs spec)

| Spec requirement | Task |
| --- | --- |
| Client-only allowlist + verification unlock | Tasks 1–2 |
| SuperAdmin-only extra Profil Saya menus | Task 3 |
| Hide nav + block URL with redirect + notice | Task 4 |
| Identitas exempt | Task 5 (+ never gate `ClientProfilePage`) |
| Stack with photo gate | Task 2 (`GatesVerifiedClientOwnRecords`) |
| Unverified + Rejected locked | Task 1 helpers (`=== Verified` only) |

No TBD/placeholder steps. Helper and trait names are consistent across tasks (`ClientMenuAccess`, `GatesVerifiedClientOwnRecords`, `RequiresSuperAdminForClientMenu`, `RedirectsLockedClientMenuAccess`).
