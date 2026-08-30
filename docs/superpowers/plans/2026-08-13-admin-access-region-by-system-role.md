# Admin Access Region by System Role Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Akses Admin requires a region only when the selected user is `admin-instansi`; `admin` users get global (null-region) access for the chosen jabatan.

**Architecture:** Keep one Filament form. Nama (`user_id`) stays the user picker and becomes `live()`. Helper methods on `AdminAccessResource` decide whether the selected user needs a region (Admin wins if a user has both roles). MorphToSelect `accessible` is visible+required only for `admin-instansi`. Create/Edit pages force `entity_type` / `entity_id` to null for global admin. `ClientAccessService` and the database schema stay unchanged.

**Tech Stack:** Laravel 12, Filament v3.2 (`Select`, `MorphToSelect`), Livewire 3, PHPUnit 11, Spatie Permission, SQLite in-memory tests (`RefreshDatabase`)

## Global Constraints

- Spec: `docs/superpowers/specs/2026-08-13-admin-access-region-by-system-role-design.md`
- Nama still selects the user; system role is read from that user (`admin` vs `admin-instansi`)
- `admin` → hide region; save `entity_type` / `entity_id` as null (global)
- `admin-instansi` → show region and require it
- Dual-role user (`admin` + `admin-instansi`) is treated as `admin` (global)
- No migration; `admin_accesses.entity_type` / `entity_id` stay nullable
- Do **not** change `ClientAccessService`, `VerifierAccessResource`, or `MasterJfResource`
- Super Admin remains the only role that can open Akses Admin
- Conventional commits; commit after each task
- PHPUnit (this repo does not use Pest for Filament tests); follow `tests/Feature/Filament/MasterJfEditFormTest.php`

---

## File structure

| File | Responsibility |
| --- | --- |
| `app/Filament/Resources/AdminAccessResource.php` | Nama query, `live()`, region visible/required, shared helpers |
| `app/Filament/Resources/AdminAccessResource/Pages/CreateAdminAccess.php` | `mutateFormDataBeforeCreate` clears region for global admin |
| `app/Filament/Resources/AdminAccessResource/Pages/EditAdminAccess.php` | `mutateFormDataBeforeSave` clears region for global admin |
| `tests/Feature/Filament/AdminAccessRoleRulesTest.php` | Helper unit coverage (roles, dual-role, form-data nulling, eligible users) |
| `tests/Feature/Filament/AdminAccessFormTest.php` | Livewire create/edit form behavior |
| `app/Services/ClientAccessService.php` | **No changes** |
| `app/Filament/Resources/VerifierAccessResource.php` | **No changes** |

---

### Task 1: Role-rule helpers on AdminAccessResource

**Files:**
- Modify: `app/Filament/Resources/AdminAccessResource.php`
- Create: `tests/Feature/Filament/AdminAccessRoleRulesTest.php`

**Interfaces:**
- Consumes: `App\Models\User`, `App\Enums\SystemRole`, Spatie `User::role()` / `hasSystemRole()`
- Produces:
  - `AdminAccessResource::eligibleUsersQuery(): \Illuminate\Database\Eloquent\Builder`
  - `AdminAccessResource::selectedUserRequiresRegion(int|string|null $userId): bool`
  - `AdminAccessResource::formDataForSelectedUser(array $data): array`

- [ ] **Step 1: Write the failing helper tests**

Create `tests/Feature/Filament/AdminAccessRoleRulesTest.php`:

```php
<?php

namespace Tests\Feature\Filament;

use App\Enums\SystemRole;
use App\Filament\Resources\AdminAccessResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccessRoleRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate(SystemRole::Admin->value, 'web');
        Role::findOrCreate(SystemRole::AdminInstansi->value, 'web');
        Role::findOrCreate(SystemRole::Verifier->value, 'web');
    }

    public function test_eligible_users_query_includes_admin_and_admin_instansi_only(): void
    {
        $admin = User::factory()->create(['name' => 'Admin User']);
        $admin->assignRole(SystemRole::Admin->value);

        $instansi = User::factory()->create(['name' => 'Instansi User']);
        $instansi->assignRole(SystemRole::AdminInstansi->value);

        $verifier = User::factory()->create(['name' => 'Verifier User']);
        $verifier->assignRole(SystemRole::Verifier->value);

        $ids = AdminAccessResource::eligibleUsersQuery()->pluck('id');

        $this->assertTrue($ids->contains($admin->id));
        $this->assertTrue($ids->contains($instansi->id));
        $this->assertFalse($ids->contains($verifier->id));
    }

    public function test_admin_user_does_not_require_region(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(SystemRole::Admin->value);

        $this->assertFalse(AdminAccessResource::selectedUserRequiresRegion($admin->id));
    }

    public function test_admin_instansi_user_requires_region(): void
    {
        $instansi = User::factory()->create();
        $instansi->assignRole(SystemRole::AdminInstansi->value);

        $this->assertTrue(AdminAccessResource::selectedUserRequiresRegion($instansi->id));
    }

    public function test_dual_role_user_is_treated_as_admin(): void
    {
        $both = User::factory()->create();
        $both->assignRole([SystemRole::Admin->value, SystemRole::AdminInstansi->value]);

        $this->assertFalse(AdminAccessResource::selectedUserRequiresRegion($both->id));
    }

    public function test_missing_user_does_not_require_region(): void
    {
        $this->assertFalse(AdminAccessResource::selectedUserRequiresRegion(null));
        $this->assertFalse(AdminAccessResource::selectedUserRequiresRegion(999999));
    }

    public function test_form_data_clears_region_for_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(SystemRole::Admin->value);

        $result = AdminAccessResource::formDataForSelectedUser([
            'user_id' => $admin->id,
            'c_role_id' => 1,
            'entity_type' => 'App\\Models\\RegDepartment',
            'entity_id' => 5,
        ]);

        $this->assertNull($result['entity_type']);
        $this->assertNull($result['entity_id']);
    }

    public function test_form_data_keeps_region_for_admin_instansi(): void
    {
        $instansi = User::factory()->create();
        $instansi->assignRole(SystemRole::AdminInstansi->value);

        $result = AdminAccessResource::formDataForSelectedUser([
            'user_id' => $instansi->id,
            'c_role_id' => 1,
            'entity_type' => 'App\\Models\\RegDepartment',
            'entity_id' => 5,
        ]);

        $this->assertSame('App\\Models\\RegDepartment', $result['entity_type']);
        $this->assertSame(5, $result['entity_id']);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=AdminAccessRoleRulesTest`

Expected: FAIL with missing methods `eligibleUsersQuery`, `selectedUserRequiresRegion`, and/or `formDataForSelectedUser` on `AdminAccessResource`.

- [ ] **Step 3: Add the helpers**

Add these imports at the top of `app/Filament/Resources/AdminAccessResource.php` if missing:

```php
use Illuminate\Database\Eloquent\Builder;
```

Add these three public static methods to `AdminAccessResource` (place them after `canDelete()`, before `form()`):

```php
public static function eligibleUsersQuery(): Builder
{
    return User::role([
        SystemRole::Admin->value,
        SystemRole::AdminInstansi->value,
    ]);
}

public static function selectedUserRequiresRegion(int|string|null $userId): bool
{
    if (blank($userId)) {
        return false;
    }

    $user = User::query()->find($userId);

    if ($user === null) {
        return false;
    }

    if ($user->hasSystemRole(SystemRole::Admin)) {
        return false;
    }

    return $user->hasSystemRole(SystemRole::AdminInstansi);
}

public static function formDataForSelectedUser(array $data): array
{
    if (! static::selectedUserRequiresRegion($data['user_id'] ?? null)) {
        $data['entity_type'] = null;
        $data['entity_id'] = null;
    }

    return $data;
}
```

Do not change the form in this task.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=AdminAccessRoleRulesTest`

Expected: PASS (7 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Resources/AdminAccessResource.php tests/Feature/Filament/AdminAccessRoleRulesTest.php
git commit -m "feat: add admin access region rules by system role"
```

---

### Task 2: Nama dropdown, live region field, and create validation

**Files:**
- Modify: `app/Filament/Resources/AdminAccessResource.php` (the `form()` method only)
- Create: `tests/Feature/Filament/AdminAccessFormTest.php`

**Interfaces:**
- Consumes: `AdminAccessResource::eligibleUsersQuery()`, `AdminAccessResource::selectedUserRequiresRegion()`, `AdminAccessResource::formDataForSelectedUser()`
- Produces: Nama select includes `admin` and `admin-instansi`; `accessible` MorphToSelect is `visible` + `required` only when `selectedUserRequiresRegion($get('user_id'))` is true; create page still uses default Filament create until Task 3

- [ ] **Step 1: Write the failing create-form tests**

Create `tests/Feature/Filament/AdminAccessFormTest.php`:

```php
<?php

namespace Tests\Feature\Filament;

use App\Enums\SystemRole;
use App\Filament\Resources\AdminAccessResource\Pages\CreateAdminAccess;
use App\Filament\Resources\AdminAccessResource\Pages\EditAdminAccess;
use App\Models\AdminAccess;
use App\Models\CRole;
use App\Models\RegDepartment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccessFormTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsSuperAdmin(): User
    {
        Role::findOrCreate(SystemRole::SuperAdmin->value, 'web');
        Role::findOrCreate(SystemRole::Admin->value, 'web');
        Role::findOrCreate(SystemRole::AdminInstansi->value, 'web');

        $user = User::factory()->create();
        $user->assignRole(SystemRole::SuperAdmin->value);
        $this->actingAs($user);

        return $user;
    }

    public function test_admin_instansi_user_cannot_be_saved_without_region(): void
    {
        $this->actingAsSuperAdmin();

        $instansi = User::factory()->create();
        $instansi->assignRole(SystemRole::AdminInstansi->value);
        $jabatan = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);

        Livewire::test(CreateAdminAccess::class)
            ->fillForm([
                'user_id' => $instansi->id,
                'c_role_id' => $jabatan->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['entity_type']);
    }

    public function test_admin_instansi_user_saves_with_region(): void
    {
        $this->actingAsSuperAdmin();

        $instansi = User::factory()->create();
        $instansi->assignRole(SystemRole::AdminInstansi->value);
        $jabatan = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $department = RegDepartment::create(['name' => 'Kementerian Hukum']);

        Livewire::test(CreateAdminAccess::class)
            ->fillForm([
                'user_id' => $instansi->id,
                'c_role_id' => $jabatan->id,
                'entity_type' => RegDepartment::class,
                'entity_id' => $department->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('admin_accesses', [
            'user_id' => $instansi->id,
            'c_role_id' => $jabatan->id,
            'entity_type' => RegDepartment::class,
            'entity_id' => $department->id,
        ]);
    }

    public function test_admin_user_saves_without_region(): void
    {
        $this->actingAsSuperAdmin();

        $admin = User::factory()->create();
        $admin->assignRole(SystemRole::Admin->value);
        $jabatan = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);

        Livewire::test(CreateAdminAccess::class)
            ->fillForm([
                'user_id' => $admin->id,
                'c_role_id' => $jabatan->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('admin_accesses', [
            'user_id' => $admin->id,
            'c_role_id' => $jabatan->id,
            'entity_type' => null,
            'entity_id' => null,
        ]);
    }
}
```

Leave `EditAdminAccess` imported; Task 3 adds the edit test to this same class.

- [ ] **Step 2: Run the instansi-without-region test to verify it fails**

Run: `php artisan test --filter=test_admin_instansi_user_cannot_be_saved_without_region`

Expected: FAIL because the form currently allows saving without `entity_type` (assertion `assertHasFormErrors(['entity_type'])` does not hold).

- [ ] **Step 3: Update the form**

In `app/Filament/Resources/AdminAccessResource.php`:

1. Add `use Filament\Forms\Get;`
2. Replace the `user_id` Select `modifyQueryUsing` and add `live()`:

```php
Forms\Components\Select::make('user_id')
    ->label(__('labels.form.user.fields.name'))
    ->searchable()
    ->relationship(
        'user',
        'name',
        modifyQueryUsing: fn (): \Illuminate\Database\Eloquent\Builder => static::eligibleUsersQuery(),
    )
    ->preload()
    ->required()
    ->live(),
```

Prefer the existing `Builder` import from Task 1 instead of the FQCN above:

```php
modifyQueryUsing: fn (): Builder => static::eligibleUsersQuery(),
```

3. Add `visible` and `required` on MorphToSelect `accessible`. Keep the same three types. MorphToSelect child fields are `entity_type` and `entity_id` (from `accessible()` morph columns), so required errors appear on `entity_type`:

```php
Forms\Components\MorphToSelect::make('accessible')
    ->types([
        Forms\Components\MorphToSelect\Type::make(RegDepartment::class)
            ->titleAttribute('name'),
        Forms\Components\MorphToSelect\Type::make(RegProvince::class)
            ->titleAttribute('name'),
        Forms\Components\MorphToSelect\Type::make(RegRegency::class)
            ->titleAttribute('name'),
    ])
    ->visible(fn (Get $get): bool => static::selectedUserRequiresRegion($get('user_id')))
    ->required(fn (Get $get): bool => static::selectedUserRequiresRegion($get('user_id'))),
```

- [ ] **Step 4: Run the create-form tests**

Run: `php artisan test --filter=AdminAccessFormTest`

Expected: PASS for the three create tests. If `assertHasFormErrors(['entity_type'])` fails with a different key, inspect the Livewire error bag and assert the actual MorphToSelect required key (likely `entity_type`; do not change the schema).

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Resources/AdminAccessResource.php tests/Feature/Filament/AdminAccessFormTest.php
git commit -m "feat: require admin access region only for admin-instansi"
```

---

### Task 3: Clear region on save when the selected user is admin

**Files:**
- Modify: `app/Filament/Resources/AdminAccessResource/Pages/CreateAdminAccess.php`
- Modify: `app/Filament/Resources/AdminAccessResource/Pages/EditAdminAccess.php`
- Modify: `tests/Feature/Filament/AdminAccessFormTest.php`

**Interfaces:**
- Consumes: `AdminAccessResource::formDataForSelectedUser(array $data): array`
- Produces: Create and Edit persist null `entity_type` / `entity_id` when the selected user does not require a region, including when switching an existing row from `admin-instansi` to `admin`

- [ ] **Step 1: Write the failing edit test**

Append these methods to `tests/Feature/Filament/AdminAccessFormTest.php` (helpers `actingAsSuperAdmin()` already exist in that class):

```php
public function test_switching_to_admin_clears_region(): void
{
    $this->actingAsSuperAdmin();

    $instansi = User::factory()->create();
    $instansi->assignRole(SystemRole::AdminInstansi->value);
    $admin = User::factory()->create();
    $admin->assignRole(SystemRole::Admin->value);
    $jabatan = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
    $department = RegDepartment::create(['name' => 'Kementerian Hukum']);

    $record = AdminAccess::create([
        'user_id' => $instansi->id,
        'c_role_id' => $jabatan->id,
        'entity_type' => RegDepartment::class,
        'entity_id' => $department->id,
    ]);

    Livewire::test(EditAdminAccess::class, ['record' => $record->getRouteKey()])
        ->fillForm([
            'user_id' => $admin->id,
            'c_role_id' => $jabatan->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $record->refresh();

    $this->assertTrue($record->user->is($admin));
    $this->assertNull($record->entity_type);
    $this->assertNull($record->entity_id);
}

public function test_creating_admin_with_region_filled_still_saves_null_region(): void
{
    $this->actingAsSuperAdmin();

    $admin = User::factory()->create();
    $admin->assignRole(SystemRole::Admin->value);
    $jabatan = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
    $department = RegDepartment::create(['name' => 'Kementerian Hukum']);

    Livewire::test(CreateAdminAccess::class)
        ->fillForm([
            'user_id' => $admin->id,
            'c_role_id' => $jabatan->id,
            'entity_type' => RegDepartment::class,
            'entity_id' => $department->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('admin_accesses', [
        'user_id' => $admin->id,
        'c_role_id' => $jabatan->id,
        'entity_type' => null,
        'entity_id' => null,
    ]);
}
```

- [ ] **Step 2: Run the edit test to verify it fails**

Run: `php artisan test --filter=test_switching_to_admin_clears_region`

Expected: FAIL — after save, `entity_type` / `entity_id` are still the department (hidden MorphToSelect does not dehydrate, so old values remain).

- [ ] **Step 3: Mutate form data on create and edit**

Replace `app/Filament/Resources/AdminAccessResource/Pages/CreateAdminAccess.php` with:

```php
<?php

namespace App\Filament\Resources\AdminAccessResource\Pages;

use App\Filament\Resources\AdminAccessResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdminAccess extends CreateRecord
{
    protected static string $resource = AdminAccessResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return AdminAccessResource::formDataForSelectedUser($data);
    }
}
```

In `app/Filament/Resources/AdminAccessResource/Pages/EditAdminAccess.php`, add this method to the class (keep `getHeaderActions()`):

```php
/**
 * @param  array<string, mixed>  $data
 * @return array<string, mixed>
 */
protected function mutateFormDataBeforeSave(array $data): array
{
    return AdminAccessResource::formDataForSelectedUser($data);
}
```

- [ ] **Step 4: Run all Admin Access tests**

Run: `php artisan test --filter=AdminAccess`

Expected: PASS (RoleRules + Form tests, including edit/clear and create-with-stale-region).

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Resources/AdminAccessResource/Pages/CreateAdminAccess.php app/Filament/Resources/AdminAccessResource/Pages/EditAdminAccess.php tests/Feature/Filament/AdminAccessFormTest.php
git commit -m "feat: clear admin access region when user is global admin"
```

---

## Verification

After all tasks:

```bash
php artisan test --filter=AdminAccess
```

Expected: all new tests pass. Spot-check that these files were not modified: `app/Services/ClientAccessService.php`, `app/Filament/Resources/VerifierAccessResource.php`, `app/Filament/Resources/MasterJfResource.php`.

Manual check (Super Admin in the panel):

1. Akses Admin → create: pick an `admin` user by nama → Ruang Regional hidden → save → row has no region.
2. Create: pick an `admin-instansi` user by nama → Ruang Regional appears and is required → save with a department/province/regency.
3. Edit the instansi row, change nama to an `admin` user → region hidden → save → region cleared.
