# SystemRole Enum Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace all hardcoded Spatie role string literals with a `SystemRole` backed enum for compile-time safety.

**Architecture:** Create `App\Enums\SystemRole` backed string enum mirroring database role names. Add `hasSystemRole()` and `hasAnySystemRole()` helpers on `User` model. Migrate all 58 call sites across 19 files from raw strings to enum references. No database changes.

**Tech Stack:** PHP 8.4 enums, Laravel Spatie Permission

---

### Task 1: Create SystemRole enum

**Files:**
- Create: `app/Enums/SystemRole.php`

**Step 1: Create the enum file**

```php
<?php

namespace App\Enums;

enum SystemRole: string
{
    case SuperAdmin    = 'super_admin';
    case PanelUser     = 'panel_user';
    case Client        = 'client';
    case Verifier      = 'verifier';
    case Admin         = 'admin';
    case AdminRegional = 'admin-regional';
    case AdminPusat    = 'admin-pusat';
    case AdminSdmBphn  = 'admin-sdm-bphn';
}
```

**Step 2: Commit**

```bash
git add app/Enums/SystemRole.php
git commit -m "feat: add SystemRole enum for type-safe role references"
```

---

### Task 2: Add helper methods to User model

**Files:**
- Modify: `app/Models/User.php`

**Step 1: Add the `use App\Enums\SystemRole` import and helper methods**

Add import at the top with other imports, then add these two methods to the `User` class:

```php
public function hasSystemRole(SystemRole $role): bool
{
    return $this->hasRole($role->value);
}

public function hasAnySystemRole(SystemRole ...$roles): bool
{
    return $this->hasRole(array_map(fn ($r) => $r->value, $roles));
}
```

**Step 2: Migrate the 3 existing `hasRole()` calls in User.php**

Line 64: `$this->hasRole('client')` → `$this->hasSystemRole(SystemRole::Client)`

Line 69: `$this->hasRole(['super_admin'])` → `$this->hasSystemRole(SystemRole::SuperAdmin)`

Line 75: `$this->hasRole(['super_admin', 'admin', 'verifier'])` → `$this->hasAnySystemRole(SystemRole::SuperAdmin, SystemRole::Admin, SystemRole::Verifier)`

**Step 3: Commit**

```bash
git add app/Models/User.php app/Enums/SystemRole.php
git commit -m "feat: add hasSystemRole and hasAnySystemRole helpers to User model"
```

---

### Task 3: Migrate Policy files

**Files:**
- Modify: `app/Policies/ClientActivityPolicy.php`
- Modify: `app/Policies/ActivityReportPolicy.php`

**Step 1: Update ClientActivityPolicy**

Add `use App\Enums\SystemRole;` import.

Replace all `hasRole([...])` calls:

- Line 12: `hasRole(['admin', 'super_admin', 'verifier', 'admin-regional', 'admin-pusat'])` → `hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin, SystemRole::Verifier, SystemRole::AdminRegional, SystemRole::AdminPusat)`
- Line 17: `hasRole(['admin', 'super_admin'])` → `hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin)`
- Line 21: `hasRole(['verifier', 'admin-regional', 'admin-pusat'])` → `hasAnySystemRole(SystemRole::Verifier, SystemRole::AdminRegional, SystemRole::AdminPusat)`
- Line 30: `hasRole(['client', 'admin', 'super_admin'])` → `hasAnySystemRole(SystemRole::Client, SystemRole::Admin, SystemRole::SuperAdmin)`
- Line 35: `hasRole(['admin', 'super_admin'])` → `hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin)`
- Line 44: `hasRole(['admin', 'super_admin'])` → `hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin)`
- Line 49: `hasRole(['admin', 'super_admin'])` → `hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin)`

**Step 2: Update ActivityReportPolicy**

Add `use App\Enums\SystemRole;` import.

Replace all `hasRole([...])` calls:

- Line 12: `hasRole(['admin', 'super_admin', 'verifier', 'admin-regional', 'admin-pusat'])` → `hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin, SystemRole::Verifier, SystemRole::AdminRegional, SystemRole::AdminPusat)`
- Line 17: same as line 12
- Line 22: `hasRole(['client', 'admin', 'super_admin'])` → `hasAnySystemRole(SystemRole::Client, SystemRole::Admin, SystemRole::SuperAdmin)`
- Line 27: `hasRole(['admin', 'super_admin'])` → `hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin)`
- Line 36: `hasRole(['admin', 'super_admin'])` → `hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin)`
- Line 41: `hasRole(['admin', 'super_admin'])` → `hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin)`

**Step 3: Commit**

```bash
git add app/Policies/ClientActivityPolicy.php app/Policies/ActivityReportPolicy.php
git commit -m "refactor: migrate Policy files to SystemRole enum"
```

---

### Task 4: Migrate Filament Resource authorization methods

**Files:**
- Modify: `app/Filament/Resources/AdminAccessResource.php`
- Modify: `app/Filament/Resources/VerifierAccessResource.php`
- Modify: `app/Filament/Resources/ClientResource.php`
- Modify: `app/Filament/Resources/ActivityReportResource.php`

**Step 1: AdminAccessResource**

Add `use App\Enums\SystemRole;` import.

- Lines 21,26,31,36: `Auth::user()?->hasRole(['super_admin'])` → `Auth::user()?->hasSystemRole(SystemRole::SuperAdmin)`
- Line 56: `User::role(['admin'])` → `User::role([SystemRole::Admin->value])`

**Step 2: VerifierAccessResource**

Add `use App\Enums\SystemRole;` import.

- Lines 24,29,34,39: `Auth::user()?->hasRole(['super_admin'])` → `Auth::user()?->hasSystemRole(SystemRole::SuperAdmin)`
- Line 57: `User::role(['verifier', 'admin-regional'])` → `User::role([SystemRole::Verifier->value, SystemRole::AdminRegional->value])`

**Step 3: ClientResource**

Add `use App\Enums\SystemRole;` import.

- Line 89: `auth()->user()->hasRole('super_admin')` → `auth()->user()->hasSystemRole(SystemRole::SuperAdmin)`
- Line 205: `auth()->user()->hasRole(['super_admin', 'admin-pusat'])` → `auth()->user()->hasAnySystemRole(SystemRole::SuperAdmin, SystemRole::AdminPusat)`
- Line 210: `auth()->user()->hasRole(['super_admin','admin-sdm-bphn'])` → `auth()->user()->hasAnySystemRole(SystemRole::SuperAdmin, SystemRole::AdminSdmBphn)`

**Step 4: ActivityReportResource**

Add `use App\Enums\SystemRole;` import.

- Lines 228-229: `$user->hasRole('admin') || $user->hasRole('super_admin')` → `$user->hasSystemRole(SystemRole::Admin) || $user->hasSystemRole(SystemRole::SuperAdmin)`
- Lines 239-240: same pattern

**Step 5: Commit**

```bash
git add app/Filament/Resources/AdminAccessResource.php app/Filament/Resources/VerifierAccessResource.php app/Filament/Resources/ClientResource.php app/Filament/Resources/ActivityReportResource.php
git commit -m "refactor: migrate Filament Resource authorization to SystemRole enum"
```

---

### Task 5: Migrate Filament Pages and Widgets

**Files:**
- Modify: `app/Filament/Pages/Verification/PointSubmissionVerificationWorkspace.php`
- Modify: `app/Filament/Pages/Dashboard.php`
- Modify: `app/Filament/Widgets/ClientsByRoleChart.php`
- Modify: `app/Filament/Widgets/ClientNumbersOverview.php`
- Modify: `app/Filament/Widgets/ClientNumbersByRoleLevelOverview.php`
- Modify: `app/Filament/Widgets/ClientNumbersByGradeOverview.php`
- Modify: `app/Filament/Widgets/ClientNumbersByStatusOverview.php`
- Modify: `app/Filament/Resources/ClientResource/Pages/ListClients.php`
- Modify: `app/Filament/Resources/CRoleResource/Concern/CanAccessClientData.php`

**Step 1: PointSubmissionVerificationWorkspace**

Add `use App\Enums\SystemRole;` import.

- Line 55: `auth()->user()->hasRole('verifier')` → `auth()->user()->hasSystemRole(SystemRole::Verifier)`

**Step 2: Dashboard**

Add `use App\Enums\SystemRole;` import.

- Line 50: `auth()->user()->hasRole(['client'])` → `auth()->user()->hasSystemRole(SystemRole::Client)`
- Line 65: `$user->hasRole(['admin', 'admin-regional', 'admin-pusat', 'verifier'])` → `$user->hasAnySystemRole(SystemRole::Admin, SystemRole::AdminRegional, SystemRole::AdminPusat, SystemRole::Verifier)`

**Step 3: All widgets (ClientsByRoleChart, ClientNumbersOverview, ClientNumbersByRoleLevelOverview, ClientNumbersByGradeOverview, ClientNumbersByStatusOverview)**

Each has the same two patterns:

- `hasRole('admin')` → `hasSystemRole(SystemRole::Admin)`
- `hasRole(['admin-regional', 'verifier','admin-pusat'])` → `hasAnySystemRole(SystemRole::AdminRegional, SystemRole::Verifier, SystemRole::AdminPusat)`

**Step 4: ListClients**

Add `use App\Enums\SystemRole;` import.

- Line 65: `hasRole('admin')` → `hasSystemRole(SystemRole::Admin)`
- Line 73: `hasRole(['admin-regional', 'verifier','admin-pusat'])` → `hasAnySystemRole(SystemRole::AdminRegional, SystemRole::Verifier, SystemRole::AdminPusat)`

**Step 5: CanAccessClientData trait**

Add `use App\Enums\SystemRole;` import.

- Line 19: `hasRole('admin')` → `hasSystemRole(SystemRole::Admin)`
- Line 27: `hasRole(['admin-regional', 'verifier', 'admin-pusat'])` → `hasAnySystemRole(SystemRole::AdminRegional, SystemRole::Verifier, SystemRole::AdminPusat)`

**Step 6: Commit**

```bash
git add app/Filament/Pages/Verification/PointSubmissionVerificationWorkspace.php app/Filament/Pages/Dashboard.php app/Filament/Widgets/ClientsByRoleChart.php app/Filament/Widgets/ClientNumbersOverview.php app/Filament/Widgets/ClientNumbersByRoleLevelOverview.php app/Filament/Widgets/ClientNumbersByGradeOverview.php app/Filament/Widgets/ClientNumbersByStatusOverview.php app/Filament/Resources/ClientResource/Pages/ListClients.php app/Filament/Resources/CRoleResource/Concern/CanAccessClientData.php
git commit -m "refactor: migrate Filament Pages and Widgets to SystemRole enum"
```

---

### Task 6: Migrate seeders and subscriber

**Files:**
- Modify: `app/Subscribers/UserEventSubscriber.php`
- Modify: `database/seeders/RoleSeeder.php`
- Modify: `database/seeders/RolePermissionSeeder.php`

**Step 1: UserEventSubscriber**

Add `use App\Enums\SystemRole;` import.

Change `protected array $defaultRoles = ['client', 'panel_user']` to:
```php
protected array $defaultRoles = [
    SystemRole::Client->value,
    SystemRole::PanelUser->value,
];
```

**Step 2: RoleSeeder**

Add `use App\Enums\SystemRole;` import.

Replace role name strings with enum values (leave `'pre-client'` as-is since it's excluded from the enum):

```php
Role::create(['name' => SystemRole::Client->value, 'guard_name' => 'web']);
Role::create(['name' => SystemRole::Admin->value, 'guard_name' => 'web']);
Role::create(['name' => SystemRole::Verifier->value, 'guard_name' => 'web']);
Role::create(['name' => SystemRole::AdminRegional->value, 'guard_name' => 'web']);
```

**Step 3: RolePermissionSeeder**

Add `use App\Enums\SystemRole;` import.

- `$superAdmin = Role::findByName('super_admin')` → `$superAdmin = Role::findByName(SystemRole::SuperAdmin->value)`
- `Role::findByName('client')` → `Role::findByName(SystemRole::Client->value)`
- `Role::findByName('verifier')` → `Role::findByName(SystemRole::Verifier->value)`
- `Role::findByName('admin')` → `Role::findByName(SystemRole::Admin->value)`

**Step 4: Commit**

```bash
git add app/Subscribers/UserEventSubscriber.php database/seeders/RoleSeeder.php database/seeders/RolePermissionSeeder.php
git commit -m "refactor: migrate seeders and subscriber to SystemRole enum"
```

---

### Task 7: Verify no remaining hardcoded role strings

**Step 1: Search for any remaining role strings**

```bash
grep -rn "hasRole(\['super_admin'" app/
grep -rn "hasRole(\['admin'" app/
grep -rn "hasRole(\['verifier'" app/
grep -rn "hasRole(\['client'" app/
grep -rn "hasRole(\['admin-regional'" app/
grep -rn "hasRole(\['admin-pusat'" app/
grep -rn "hasRole(\['admin-sdm-bphn'" app/
grep -rn "hasRole('super_admin'" app/
grep -rn "hasRole('admin'" app/
grep -rn "hasRole('verifier'" app/
grep -rn "hasRole('client'" app/
grep -rn "User::role(\[" app/
```

All should return zero results (except `config/filament-shield.php` which is intentionally excluded).

**Step 2: Run static analysis**

```bash
php artisan route:cache && php artisan config:cache
```

If this succeeds, all class references are valid.

**Step 3: Commit verification**

```bash
git add -A
git commit --allow-empty -m "chore: verify SystemRole enum migration complete"
```