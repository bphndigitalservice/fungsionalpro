# Filament v3 → v5 Upgrade Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Upgrade from Filament v3.3 to Filament v5 (Livewire 4) in two PRs, keeping all five plugins, migrating to the v4 directory structure, with expanded Filament Livewire tests and a manual checklist before each merge.

**Architecture:** Write regression tests against current Filament v3 first (baseline). PR1 runs `filament/upgrade` v4 + plugin majors + `filament:upgrade-directory-structure-to-v4` + theme/Shield fixes until the suite is green. PR2 runs `filament/upgrade` v5 + Livewire 4 alignment and re-runs the same suite.

**Tech Stack:** PHP 8.4, Laravel 13 (current branch), Filament 3→4→5, Livewire 3→4, Tailwind CSS 4, PHPUnit 12, Sail, bun, Spatie Permission, plugins: Shield, Media Action, Invite, JSON Column, Banner

## Global Constraints

- Spec: `docs/superpowers/specs/2026-07-20-filament-v5-upgrade-design.md`
- Do **not** remove plugins; stop and fix Composer constraints if resolve fails
- Two PRs only: `upgrade/filament-v4` then `upgrade/filament-v5` (from merged PR1)
- Migrate to official v4 resource/cluster directory structure in PR1
- No optional API modernizations beyond upgrade scripts + official guides
- All PHP/Artisan/Composer/Node via `vendor/bin/sail`
- After PHP edits: `vendor/bin/sail bin pint --dirty --format agent`
- Base branch: create Filament branches from the branch that already has Laravel 13 (`upgrade/laravel-13` or `main` after that merges)—do not start from stale Laravel 12

---

## File structure

| File / area | Responsibility |
| --- | --- |
| `tests/TestCase.php` | Shared test bootstrapping (`RefreshDatabase` optional per class) |
| `tests/Concerns/InteractsWithFilament.php` | Helpers: create super-admin user, set Filament panel, grant permissions |
| `tests/Feature/Filament/Auth/*` | Login / register / panel access |
| `tests/Feature/Filament/DashboardTest.php` | Authenticated dashboard |
| `tests/Feature/Filament/Resources/*` | Representative resource Livewire tests |
| `tests/Feature/Filament/Pages/*` | Custom pages (verification, client profile) |
| `tests/Feature/Filament/Plugins/*` | Invite, MediaAction, Banner, Shield smoke |
| `docs/superpowers/checklists/filament-v4-manual.md` | Manual QA for PR1 |
| `docs/superpowers/checklists/filament-v5-manual.md` | Manual QA for PR2 |
| `composer.json` / `composer.lock` | Filament + plugin version bumps |
| `app/Filament/**` | Scripted + manual upgrade / directory moves |
| `app/Providers/Filament/AdminPanelProvider.php` | Plugin + theme + discover paths |
| `resources/css/filament/admin/theme.css` | Tailwind v4 `@source` theme |
| `vite.config.js` | Register Filament theme input if required by v4 |
| `app/Livewire/*` | Livewire 4 fixes in PR2 |
| `config/filament.php` | Publish only if needed |
| `config/filament-shield.php` | Shield v4 rewrite config |

---

## Phase A — Baseline tests on Filament v3 (before any upgrade)

### Task 1: Branch + Filament test helpers

**Files:**
- Create branch: `upgrade/filament-v4` from Laravel-13 base
- Modify: `tests/TestCase.php`
- Create: `tests/Concerns/InteractsWithFilament.php`

**Interfaces:**
- Consumes: `App\Models\User`, `App\Enums\SystemRole`, `Spatie\Permission\Models\Role`, `Filament\Facades\Filament`
- Produces: `InteractsWithFilament::actingAsFilamentUser()`, `createSuperAdmin()`, `ensureSystemRoles()`

- [ ] **Step 1: Create branch**

```bash
git fetch origin
git checkout upgrade/laravel-13   # or main if Laravel 13 already merged
git pull
git checkout -b upgrade/filament-v4
```

Expected: on `upgrade/filament-v4`, clean working tree.

- [ ] **Step 2: Write `tests/Concerns/InteractsWithFilament.php`**

```php
<?php

namespace Tests\Concerns;

use App\Enums\SystemRole;
use App\Models\User;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

trait InteractsWithFilament
{
    protected function setUpFilamentPanel(string $panelId = 'admin'): void
    {
        Filament::setCurrentPanel(Filament::getPanel($panelId));
    }

    protected function ensureSystemRoles(): void
    {
        foreach (SystemRole::cases() as $role) {
            Role::findOrCreate($role->value, 'web');
        }
    }

    protected function createSuperAdmin(array $attributes = []): User
    {
        $this->ensureSystemRoles();

        $user = User::factory()->create($attributes);
        $user->assignRole(SystemRole::SuperAdmin->value);

        return $user;
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function createUserWithPermissions(array $permissions, SystemRole $role = SystemRole::Admin): User
    {
        $this->ensureSystemRoles();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user = User::factory()->create();
        $user->assignRole($role->value);
        $user->givePermissionTo($permissions);

        return $user;
    }

    protected function actingAsFilamentUser(User $user): static
    {
        $this->setUpFilamentPanel();

        return $this->actingAs($user);
    }
}
```

- [ ] **Step 3: Keep `tests/TestCase.php` thin (Laravel boots app automatically)**

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    //
}
```

- [ ] **Step 4: Commit**

```bash
git add tests/Concerns/InteractsWithFilament.php tests/TestCase.php
git commit -m "$(cat <<'EOF'
test: add Filament panel test helpers for upgrade baseline

EOF
)"
```

---

### Task 2: Auth + dashboard regression tests

**Files:**
- Create: `tests/Feature/Filament/Auth/LoginTest.php`
- Create: `tests/Feature/Filament/DashboardTest.php`
- Modify: `tests/Feature/ExampleTest.php` (fix or delete misleading `/` → 200 assertion if panel redirects guests)

**Interfaces:**
- Consumes: `InteractsWithFilament`, `App\Filament\Pages\Authx\Login`, `App\Filament\Pages\Dashboard`
- Produces: green auth/dashboard coverage on v3

- [ ] **Step 1: Write failing/correcting guest login page test**

```php
<?php

namespace Tests\Feature\Filament\Auth;

use App\Filament\Pages\Authx\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithFilament;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use InteractsWithFilament;
    use RefreshDatabase;

    public function test_login_page_renders_for_guests(): void
    {
        $this->setUpFilamentPanel();

        Livewire::test(Login::class)
            ->assertSuccessful();
    }

    public function test_authenticated_super_admin_can_reach_dashboard_route(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAsFilamentUser($user)
            ->get('/')
            ->assertSuccessful();
    }
}
```

- [ ] **Step 2: Run login tests**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Filament/Auth/LoginTest.php
```

Expected: PASS (if fail on permissions/middleware, adjust helper—do not skip).

- [ ] **Step 3: Write dashboard Livewire test**

```php
<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithFilament;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use InteractsWithFilament;
    use RefreshDatabase;

    public function test_super_admin_can_render_dashboard(): void
    {
        $user = $this->createSuperAdmin();
        $this->actingAsFilamentUser($user);

        Livewire::test(Dashboard::class)
            ->assertSuccessful();
    }
}
```

- [ ] **Step 4: Run dashboard test**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Filament/DashboardTest.php
```

Expected: PASS.

- [ ] **Step 5: Fix `ExampleTest` so CI is honest**

Replace with a health-check that matches reality (Filament login redirect or `/up`):

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_health_endpoint_is_ok(): void
    {
        $this->get('/up')->assertSuccessful();
    }
}
```

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/Filament tests/Feature/ExampleTest.php
git commit -m "$(cat <<'EOF'
test: add Filament auth and dashboard baseline coverage

EOF
)"
```

---

### Task 3: Representative resource Livewire tests

**Files:**
- Create: `tests/Feature/Filament/Resources/ClientDocumentTypeResourceTest.php`
- Create: `tests/Feature/Filament/Resources/UserResourceTest.php`
- Create: `tests/Feature/Filament/Resources/ClientResourceListTest.php`

**Interfaces:**
- Consumes: Resource page classes under `app/Filament/Resources/*/Pages`, factories, Shield permissions for the resource
- Produces: list/search/create coverage that must stay green through v4/v5

- [ ] **Step 1: Write ClientDocumentType list + create tests**

Grant the permissions Shield uses for this resource (names may be `view_any_client::document::type`, etc.—discover via `php artisan shield:generate --dry-run` or existing DB; prefer matching `RolePermissionSeeder` style). Example assuming standard Shield names—adjust to actual permission strings from `app/Policies` / seeder:

```php
<?php

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\ClientDocumentTypeResource\Pages\CreateClientDocumentType;
use App\Filament\Resources\ClientDocumentTypeResource\Pages\ListClientDocumentTypes;
use App\Models\ClientDocumentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithFilament;
use Tests\TestCase;

class ClientDocumentTypeResourceTest extends TestCase
{
    use InteractsWithFilament;
    use RefreshDatabase;

    public function test_super_admin_can_list_document_types(): void
    {
        $user = $this->createSuperAdmin();
        $types = ClientDocumentType::factory()->count(3)->create();

        $this->actingAsFilamentUser($user);

        Livewire::test(ListClientDocumentTypes::class)
            ->assertCanSeeTableRecords($types);
    }

    public function test_super_admin_can_create_document_type(): void
    {
        $user = $this->createSuperAdmin();
        $this->actingAsFilamentUser($user);

        Livewire::test(CreateClientDocumentType::class)
            ->fillForm([
                'type' => 'SK',
                'description' => 'Surat Keputusan',
                'is_required' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(ClientDocumentType::class, [
            'type' => 'SK',
            'description' => 'Surat Keputusan',
        ]);
    }
}
```

- [ ] **Step 2: Run document type tests; fix permission/policy until green**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Filament/Resources/ClientDocumentTypeResourceTest.php
```

If authorization fails: either assign concrete permissions via `createUserWithPermissions([...])` or ensure SuperAdmin bypass still works under Shield on this app.

- [ ] **Step 3: Write UserResource list + InviteAction presence test**

```php
<?php

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithFilament;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use InteractsWithFilament;
    use RefreshDatabase;

    public function test_super_admin_can_list_users(): void
    {
        $actor = $this->createSuperAdmin(['email' => 'actor@example.com']);
        $other = User::factory()->create(['name' => 'Other User']);

        $this->actingAsFilamentUser($actor);

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$other])
            ->searchTable('Other User')
            ->assertCanSeeTableRecords([$other]);
    }

    public function test_user_table_includes_invite_action(): void
    {
        $actor = $this->createSuperAdmin();
        $this->actingAsFilamentUser($actor);

        Livewire::test(ListUsers::class)
            ->assertTableActionExists('invite');
    }
}
```

Confirm Invite action name with `InviteAction::make()` (default name is often `invite`)—adjust assertion to the real action name if different.

- [ ] **Step 4: Write ClientResource list smoke**

```php
<?php

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages\ListClients;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithFilament;
use Tests\TestCase;

class ClientResourceListTest extends TestCase
{
    use InteractsWithFilament;
    use RefreshDatabase;

    public function test_super_admin_can_render_client_list(): void
    {
        $user = $this->createSuperAdmin();
        $this->actingAsFilamentUser($user);

        Livewire::test(ListClients::class)
            ->assertSuccessful();
    }
}
```

- [ ] **Step 5: Run all resource tests**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Filament/Resources
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/Filament/Resources
git commit -m "$(cat <<'EOF'
test: add Filament resource baseline Livewire coverage

EOF
)"
```

---

### Task 4: Custom pages + MediaAction + Banner + Shield smoke

**Files:**
- Create: `tests/Feature/Filament/Pages/ClientProfilePageTest.php`
- Create: `tests/Feature/Filament/Pages/ClientIdentityVerificationWorkspaceTest.php`
- Create: `tests/Feature/Filament/Plugins/MediaActionSmokeTest.php`
- Create: `tests/Feature/Filament/Plugins/BannerPluginTest.php`
- Create: `tests/Feature/Filament/Plugins/ShieldRoleResourceTest.php`

**Interfaces:**
- Consumes: custom page classes, `HasPageShield` permissions from seeder (`page_ClientProfilePage`, `page_ClientIdentityVerificationWorkspace`, `banner-manager`), MediaAction on `ClientActivityResource` / Livewire tables
- Produces: plugin/page smoke tests required by the spec

- [ ] **Step 1: Client profile page render (client role + permission)**

```php
<?php

namespace Tests\Feature\Filament\Pages;

use App\Enums\SystemRole;
use App\Filament\Pages\Client\ClientProfilePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithFilament;
use Tests\TestCase;

class ClientProfilePageTest extends TestCase
{
    use InteractsWithFilament;
    use RefreshDatabase;

    public function test_client_with_permission_can_render_profile_page(): void
    {
        $user = $this->createUserWithPermissions(
            ['page_ClientProfilePage'],
            SystemRole::Client,
        );

        // Attach client record if page requires $user->client — use factory/relation as app expects.
        $this->actingAsFilamentUser($user);

        Livewire::test(ClientProfilePage::class)
            ->assertSuccessful();
    }
}
```

If the page requires a `Client` model, create one linked to `$user` before `Livewire::test` (inspect `Client` factory / model).

- [ ] **Step 2: Verification workspace page for verifier**

```php
<?php

namespace Tests\Feature\Filament\Pages;

use App\Enums\SystemRole;
use App\Filament\Pages\Verification\ClientIdentityVerificationWorkspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithFilament;
use Tests\TestCase;

class ClientIdentityVerificationWorkspaceTest extends TestCase
{
    use InteractsWithFilament;
    use RefreshDatabase;

    public function test_verifier_can_render_identity_workspace(): void
    {
        $user = $this->createUserWithPermissions(
            ['page_ClientIdentityVerificationWorkspace'],
            SystemRole::Verifier,
        );

        $this->actingAsFilamentUser($user);

        Livewire::test(ClientIdentityVerificationWorkspace::class)
            ->assertSuccessful();
    }
}
```

- [ ] **Step 3: MediaAction exists on ClientActivity list (or Livewire table)**

```php
<?php

namespace Tests\Feature\Filament\Plugins;

use App\Filament\Resources\ClientActivityResource\Pages\ListClientActivities;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithFilament;
use Tests\TestCase;

class MediaActionSmokeTest extends TestCase
{
    use InteractsWithFilament;
    use RefreshDatabase;

    public function test_client_activity_table_registers_media_action(): void
    {
        $user = $this->createSuperAdmin();
        $this->actingAsFilamentUser($user);

        Livewire::test(ListClientActivities::class)
            ->assertTableActionExists('media'); // adjust name to MediaAction::make() name
    }
}
```

Inspect `ClientActivityResource` for the exact action name / whether list page is authorized for SuperAdmin.

- [ ] **Step 4: Banner plugin registered + permission gate**

```php
<?php

namespace Tests\Feature\Filament\Plugins;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kenepa\Banner\BannerPlugin;
use Tests\Concerns\InteractsWithFilament;
use Tests\TestCase;

class BannerPluginTest extends TestCase
{
    use InteractsWithFilament;
    use RefreshDatabase;

    public function test_banner_plugin_is_registered_on_admin_panel(): void
    {
        $this->setUpFilamentPanel();

        $plugins = collect(Filament::getCurrentPanel()->getPlugins())
            ->map(fn ($plugin) => $plugin::class);

        $this->assertTrue($plugins->contains(BannerPlugin::class));
    }
}
```

- [ ] **Step 5: Shield RoleResource list for super admin**

```php
<?php

namespace Tests\Feature\Filament\Plugins;

use App\Filament\Resources\Shield\RoleResource\Pages\ListRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithFilament;
use Tests\TestCase;

class ShieldRoleResourceTest extends TestCase
{
    use InteractsWithFilament;
    use RefreshDatabase;

    public function test_super_admin_can_list_shield_roles(): void
    {
        $user = $this->createSuperAdmin();
        $this->actingAsFilamentUser($user);

        Livewire::test(ListRoles::class)
            ->assertSuccessful();
    }
}
```

Note: after Shield v4 rewrite, namespaces/pages may move—update this test in Task 9 if paths change.

- [ ] **Step 6: Run Phase A suite**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Filament
```

Expected: all PASS on Filament v3.

- [ ] **Step 7: Commit**

```bash
git add tests/Feature/Filament
git commit -m "$(cat <<'EOF'
test: add Filament pages and plugin baseline smoke coverage

EOF
)"
```

---

## Phase B — PR1 Filament v3 → v4

### Task 5: Run Filament v4 upgrade script + Composer bumps

**Files:**
- Modify: `composer.json`, `composer.lock`
- Mass-modify: `app/Filament/**` (via script)
- Possibly: `app/Providers/**`, `app/Livewire/**`

**Interfaces:**
- Consumes: Filament v3 codebase + `filament/upgrade` ^4
- Produces: Filament ^4.0 + plugin majors installed; scripted code transforms applied

- [ ] **Step 1: Install upgrade tool**

```bash
vendor/bin/sail composer require filament/upgrade:"^4.0" -W --dev
```

Expected: package installs (Rector/PHPStan constraints satisfied).

- [ ] **Step 2: Run upgrade script**

```bash
vendor/bin/sail php vendor/bin/filament-v4
```

Follow printed Composer commands exactly. Typical follow-up:

```bash
vendor/bin/sail composer require filament/filament:"^4.0" bezhansalleh/filament-shield:"^4.0" hugomyb/filament-media-action:"^4.0" tapp/filament-invite:"^2.0" kenepa/banner:"^1.0" -W --no-update
```

Confirm JSON Column Filament-4 constraint on Packagist at run time (likely `^3.0`), then:

```bash
vendor/bin/sail composer require valentin-morice/filament-json-column:"^3.0" -W --no-update
vendor/bin/sail composer update
```

If resolve fails: **stop**, adjust versions—do not remove plugins.

- [ ] **Step 3: Remove upgrade package**

```bash
vendor/bin/sail composer remove filament/upgrade --dev
```

- [ ] **Step 4: Commit dependency + scripted code changes**

```bash
git add -A
git commit -m "$(cat <<'EOF'
chore: upgrade Filament and plugins to v4-compatible majors

EOF
)"
```

---

### Task 6: Migrate directory structure to v4

**Files:**
- Move/rename under `app/Filament/Resources/**`, `app/Filament/Clusters/**`
- Update imports across app/tests

**Interfaces:**
- Consumes: Filament v4 `filament:upgrade-directory-structure-to-v4`
- Produces: official v4 layout; discover paths still valid

- [ ] **Step 1: Dry-run**

```bash
vendor/bin/sail artisan filament:upgrade-directory-structure-to-v4 --dry-run
```

Review planned moves.

- [ ] **Step 2: Apply**

```bash
vendor/bin/sail artisan filament:upgrade-directory-structure-to-v4
```

- [ ] **Step 3: Fix broken same-namespace references**

Search for failed imports / wrong class names:

```bash
vendor/bin/sail artisan test --compact tests/Feature/Filament 2>&1 | head -100
```

Fix any `Class not found` in Resources/Clusters/Pages and update test imports to new namespaces.

- [ ] **Step 4: Verify AdminPanelProvider discover paths still match**

`app/Providers/Filament/AdminPanelProvider.php` should still discover:

- `app_path('Filament/Resources')` → `App\Filament\Resources`
- `app_path('Filament/Pages')` → `App\Filament\Pages`
- `app_path('Filament/Clusters')` → `App\Filament\Clusters`
- `app_path('Filament/Widgets')` → `App\Filament\Widgets`

Adjust only if the migrator changes roots.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "$(cat <<'EOF'
refactor: migrate Filament resources and clusters to v4 directories

EOF
)"
```

---

### Task 7: Theme, Vite, and Banner `@source` updates

**Files:**
- Modify: `resources/css/filament/admin/theme.css`
- Modify: `resources/css/filament/admin/tailwind.config.js` (remove obsolete content paths per guides)
- Modify: `vite.config.js` (add theme input if Filament v4 requires `->viteTheme(...)`)
- Modify: `app/Providers/Filament/AdminPanelProvider.php` (`viteTheme` registration)

**Interfaces:**
- Consumes: Filament v4 theme docs + kenepa/banner 1.x upgrade notes
- Produces: built CSS including Banner views

- [ ] **Step 1: Update theme CSS**

Replace `theme.css` with v4-style sources (paths relative to theme file—verify depth):

```css
@import '../../../../vendor/filament/filament/resources/css/theme.css';

@source '../../../../app/Filament/**/*';
@source '../../../../resources/views/filament/**/*';
@source '../../../../vendor/kenepa/banner/resources/**/*.blade.php';
```

Remove obsolete `@config 'tailwind.config.js'` if Filament v4 theme no longer uses it; delete or gut `tailwind.config.js` content entries that duplicate `@source`.

- [ ] **Step 2: Register theme on the panel + Vite**

In `AdminPanelProvider`:

```php
->viteTheme('resources/css/filament/admin/theme.css')
```

In `vite.config.js`:

```js
input: [
    "resources/css/app.css",
    "resources/js/app.js",
    "resources/css/filament/admin/theme.css",
],
```

- [ ] **Step 3: Build assets**

```bash
vendor/bin/sail bun run build
```

Expected: build succeeds; theme CSS in manifest.

- [ ] **Step 4: Commit**

```bash
git add resources/css/filament vite.config.js app/Providers/Filament/AdminPanelProvider.php
git commit -m "$(cat <<'EOF'
fix: adapt Filament admin theme for v4 and Banner sources

EOF
)"
```

---

### Task 8: Shield v4 rewrite + panel plugin wiring

**Files:**
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Modify: `app/Providers/AppServiceProvider.php` (FilamentShield calls)
- Modify: `app/Models/User.php` (HasPanelShield / replacements)
- Modify: pages/widgets using `HasPageShield` / `HasWidgetShield`
- Replace or regenerate: `app/Filament/Resources/Shield/**`
- Publish/update: `config/filament-shield.php`

**Interfaces:**
- Consumes: Shield 4.x upgrade guide (complete rewrite; `HasShieldPermissions` deprecated)
- Produces: working roles UI + permission checks compatible with existing Spatie data

- [ ] **Step 1: Follow Shield upgrade section**

From Shield README Upgrade:

1. Remove old published Shield config if instructed, republish for 4.x
2. `composer` already on `^4.0`
3. Run Shield setup commands as documented (e.g. `shield:setup` / `shield:generate` as required—use `--no-interaction` and read current `--help`)
4. Preserve existing roles/permissions in DB; do not wipe production data in migrations

- [ ] **Step 2: Update `AdminPanelProvider` plugins**

Keep:

```php
->plugins([
    \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
    BannerPlugin::make()->persistsBannersInDatabase()
        ->navigationGroup(__('labels.nav.system'))
        ->bannerManagerAccessPermission('banner-manager'),
])
```

Adjust method names if Banner 1.x / Shield 4.x APIs renamed (check package source after install).

- [ ] **Step 3: Update User / page / widget Shield traits per 4.x docs**

Replace removed contracts/traits with 4.x equivalents so `canAccessPanel` and page authorization still work.

- [ ] **Step 4: Run Shield-related tests**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Filament/Plugins/ShieldRoleResourceTest.php tests/Feature/Filament/Auth tests/Feature/Filament/Pages
```

Expected: PASS after namespace/API fixes.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "$(cat <<'EOF'
fix: migrate Filament Shield to v4 plugin APIs

EOF
)"
```

---

### Task 9: Manual v4 guide leftovers + make full Filament suite green

**Files:**
- Any remaining `app/Filament/**`, `app/Livewire/**`, exporters, actions broken by v4
- Update tests if class paths changed

**Interfaces:**
- Consumes: [Filament v4 upgrade guide](https://filamentphp.com/docs/4.x/upgrade-guide) manual sections (Panels, Forms, Tables, Actions, Infolists, Widgets, Exports)
- Produces: green `tests/Feature/Filament`

- [ ] **Step 1: Run full Filament suite; collect failures**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Filament
```

- [ ] **Step 2: Fix failures in dependency order**

1. Autoload / namespace / directory path errors  
2. Form/Table method signature changes (`Form` → Schema APIs if script incomplete)  
3. Action namespace moves  
4. Custom page `protected static string $view` / layout changes  
5. Exporter API changes  

Prefer minimal API fixes over rewrites.

- [ ] **Step 3: Pint dirty PHP**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 4: Re-run suite**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Filament
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "$(cat <<'EOF'
fix: resolve Filament v4 upgrade fallout for panel code

EOF
)"
```

---

### Task 10: PR1 manual checklist + open PR

**Files:**
- Create: `docs/superpowers/checklists/filament-v4-manual.md`

- [ ] **Step 1: Write checklist file**

```markdown
# Filament v4 manual checklist

- [ ] Login / logout
- [ ] Register + email verification prompt
- [ ] Profile page
- [ ] Dashboard (admin + client widgets as applicable)
- [ ] Navigation groups load; Reference cluster works
- [ ] Client list / view / edit smoke
- [ ] Client document types CRUD
- [ ] Users list + Invite action
- [ ] Shield roles UI
- [ ] Banner manager (with `banner-manager` permission)
- [ ] MediaAction opens media (activity / education)
- [ ] Verification workspaces accept/reject smoke
- [ ] Exports download (activity report / client activity)
- [ ] Database notifications bell
- [ ] Theme light mode looks correct (custom CSS loaded)
```

- [ ] **Step 2: Execute checklist against local Sail app**

```bash
vendor/bin/sail open
```

Mark items in the checklist file as you verify.

- [ ] **Step 3: Push and open PR1**

```bash
git add docs/superpowers/checklists/filament-v4-manual.md
git commit -m "$(cat <<'EOF'
docs: add Filament v4 manual regression checklist

EOF
)"
git push -u origin upgrade/filament-v4
gh pr create --title "chore: upgrade Filament to v4" --body "$(cat <<'EOF'
## Summary
- Upgrade Filament + plugins to v4-compatible majors
- Migrate resources/clusters to v4 directory structure
- Expand Filament Livewire regression tests
- Theme/Banner/Shield adaptations

## Test plan
- [x] `vendor/bin/sail artisan test --compact tests/Feature/Filament`
- [ ] Manual checklist in `docs/superpowers/checklists/filament-v4-manual.md`

EOF
)"
```

Do not start PR2 until this PR is merged and checklist signed off.

---

## Phase C — PR2 Filament v4 → v5

### Task 11: Branch + Filament v5 upgrade script

**Files:**
- Branch: `upgrade/filament-v5` from merged v4 branch
- Modify: `composer.json`, `composer.lock`
- Scripted code changes as emitted by `filament-v5`

**Interfaces:**
- Consumes: Filament v4 app
- Produces: Filament ^5.0 + Livewire ^4.0

- [ ] **Step 1: Branch**

```bash
git checkout main   # or the branch that contains merged Filament v4
git pull
git checkout -b upgrade/filament-v5
```

- [ ] **Step 2: Install and run v5 upgrade**

```bash
vendor/bin/sail composer require filament/upgrade:"^5.0" -W --dev
vendor/bin/sail php vendor/bin/filament-v5
```

Run the Composer commands the script prints, including:

```bash
vendor/bin/sail composer require filament/filament:"^5.0" -W --no-update
```

If JSON Column needs Filament-5 major (`^4.0`), bump it here. Keep all five plugins.

```bash
vendor/bin/sail composer update
vendor/bin/sail composer remove filament/upgrade --dev
```

- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "$(cat <<'EOF'
chore: upgrade Filament to v5 and Livewire 4

EOF
)"
```

---

### Task 12: Livewire 4 custom component fixes

**Files:**
- Modify: `app/Livewire/ClientActivityTable.php`
- Modify: `app/Livewire/ClientEducationInfolist.php`
- Modify: `app/Livewire/ProfileCompletionWidget.php`
- Any other custom Livewire under `app/`

**Interfaces:**
- Consumes: [Livewire 4 upgrade guide](https://livewire.laravel.com/docs/upgrading)
- Produces: components boot without deprecation/runtime errors under Filament pages

- [ ] **Step 1: Diff Livewire breaking changes against custom components**

Check for removed lifecycle APIs, `wire:model` defaults, layout/component namespaces, and test failures.

- [ ] **Step 2: Fix components minimally; re-run Filament suite**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Filament
```

- [ ] **Step 3: Rebuild assets**

```bash
vendor/bin/sail bun run build
```

- [ ] **Step 4: Commit**

```bash
git add app/Livewire tests
git commit -m "$(cat <<'EOF'
fix: adapt custom Livewire components for Livewire 4

EOF
)"
```

---

### Task 13: PR2 verification + PR

**Files:**
- Create: `docs/superpowers/checklists/filament-v5-manual.md`

- [ ] **Step 1: Copy v4 checklist to v5 file; add Livewire-4 notes**

```markdown
# Filament v5 manual checklist

(Same items as v4 checklist, plus:)

- [ ] Custom Livewire tables/infolists on client pages work
- [ ] No console/Livewire errors on verification workspace actions
```

- [ ] **Step 2: Full automated suite**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Filament
vendor/bin/sail bin pint --dirty --format agent
```

Expected: PASS.

- [ ] **Step 3: Manual checklist + open PR**

```bash
git add docs/superpowers/checklists/filament-v5-manual.md
git commit -m "$(cat <<'EOF'
docs: add Filament v5 manual regression checklist

EOF
)"
git push -u origin upgrade/filament-v5
gh pr create --title "chore: upgrade Filament to v5" --body "$(cat <<'EOF'
## Summary
- Upgrade Filament v4 → v5 (Livewire 4)
- Align plugins to Filament 5 constraints
- Re-verify Filament regression suite + manual checklist

## Test plan
- [x] `vendor/bin/sail artisan test --compact tests/Feature/Filament`
- [ ] Manual checklist in `docs/superpowers/checklists/filament-v5-manual.md`

EOF
)"
```

---

## Plan self-review

| Spec requirement | Task(s) |
| --- | --- |
| Keep all five plugins | Tasks 5, 11 |
| Two PRs v3→v4 then v4→v5 | Tasks 5–10, 11–13 |
| v4 directory structure in PR1 | Task 6 |
| Official upgrade scripts | Tasks 5, 11 |
| Theme / Banner sources | Task 7 |
| Shield rewrite | Task 8 |
| Expand Livewire tests + manual checklist | Tasks 2–4, 10, 13 |
| No optional modernizations | Tasks 5–9 (fix fallout only) |
| Stop if Composer cannot resolve plugins | Task 5 / 11 steps |

No intentional TBD placeholders remain; plugin exact minors are confirmed at Composer time against Packagist as specified.
