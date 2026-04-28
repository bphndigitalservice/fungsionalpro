# AdminInstansi Role Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add `AdminInstansi` role to `SystemRole` enum and grant client-facing resource access while excluding system-admin resources.

**Architecture:** New enum value + targeted additions to policy/viewAny groups in existing files. No new files, no migrations, no database changes beyond seeding the role.

**Tech Stack:** PHP 8.4 enums, Laravel Spatie Permission, Filament

---

### Task 1: Add enum value and seeder

**Files:**
- Modify: `app/Enums/SystemRole.php`
- Modify: `database/seeders/RoleSeeder.php`

**Step 1: Add AdminInstansi case to SystemRole enum**

```php
case AdminInstansi = 'admin-instansi';
```

Add after `case AdminSdmBphn`.

**Step 2: Add to RoleSeeder**

Add `Role::create(['name' => SystemRole::AdminInstansi->value, 'guard_name' => 'web']);` after the existing `AdminRegional` line.

**Step 3: Commit**

```bash
git add app/Enums/SystemRole.php database/seeders/RoleSeeder.php
git commit -m "feat: add AdminInstansi to SystemRole enum and RoleSeeder"
```

---

### Task 2: Add AdminInstansi to Policy files

**Files:**
- Modify: `app/Policies/ClientActivityPolicy.php`
- Modify: `app/Policies/ActivityReportPolicy.php`

**Step 1: ClientActivityPolicy**

Add `SystemRole::AdminInstansi` to these methods:

- `viewAny()` — add `SystemRole::AdminInstansi` to the role list
- `view()` — likely in the admin/super_admin branch, add `SystemRole::AdminInstansi`
- `create()` — add `SystemRole::AdminInstansi` to the role list
- `update()` — add `SystemRole::AdminInstansi` to the admin/super_admin branch

**Step 2: ActivityReportPolicy**

Add `SystemRole::AdminInstansi` to:

- `viewAny()` — add to role list
- `view()` — add to role list
- `create()` — add to role list

Do NOT add to `update()` or `delete()` — those stay admin/super_admin only.

**Step 3: Commit**

```bash
git add app/Policies/ClientActivityPolicy.php app/Policies/ActivityReportPolicy.php
git commit -m "feat: add AdminInstansi to Policy access groups"
```

---

### Task 3: Add AdminInstansi to Filament Resources and Pages

**Files:**
- Modify: `app/Filament/Resources/ActivityReportResource.php` — add to `shouldRegisterNavigation()` and `canViewAny()`

**Step 1: ActivityReportResource**

In `shouldRegisterNavigation()` and `canViewAny()`, add `SystemRole::AdminInstansi` alongside `SystemRole::Admin` in the role checks.

**Step 2: Commit**

```bash
git add app/Filament/Resources/ActivityReportResource.php
git commit -m "feat: add AdminInstansi to ActivityReportResource navigation and access"
```

---

### Task 4: Add AdminInstansi to Widget data scoping

**Files:**
- Modify: `app/Filament/Widgets/ClientsByRoleChart.php`
- Modify: `app/Filament/Widgets/ClientNumbersOverview.php`
- Modify: `app/Filament/Widgets/ClientNumbersByRoleLevelOverview.php`
- Modify: `app/Filament/Widgets/ClientNumbersByGradeOverview.php`
- Modify: `app/Filament/Widgets/ClientNumbersByStatusOverview.php`

**Step 1: Each widget**

In the admin-scoped `elseif` branches, add `SystemRole::AdminInstansi`:

- `hasSystemRole(SystemRole::Admin)` → `hasAnySystemRole(SystemRole::Admin, SystemRole::AdminInstansi)`
- `hasAnySystemRole(SystemRole::AdminRegional, SystemRole::Verifier, SystemRole::AdminPusat)` — add `SystemRole::AdminInstansi`

Pattern is: wherever `Admin` appears in a role group, `AdminInstansi` should appear too.

**Step 2: Commit**

```bash
git add app/Filament/Widgets/ClientsByRoleChart.php app/Filament/Widgets/ClientNumbersOverview.php app/Filament/Widgets/ClientNumbersByRoleLevelOverview.php app/Filament/Widgets/ClientNumbersByGradeOverview.php app/Filament/Widgets/ClientNumbersByStatusOverview.php
git commit -m "feat: add AdminInstansi to widget data scoping"
```

---

### Task 5: Add AdminInstansi to ListClients and CanAccessClientData

**Files:**
- Modify: `app/Filament/Resources/ClientResource/Pages/ListClients.php`
- Modify: `app/Filament/Resources/CRoleResource/Concern/CanAccessClientData.php`

**Step 1: ListClients**

Add `SystemRole::AdminInstansi` to admin role checks and scoped role checks, same pattern as widgets.

**Step 2: CanAccessClientData trait**

Add `SystemRole::AdminInstansi` to admin branch and scoped-data branch.

**Step 3: Commit**

```bash
git add app/Filament/Resources/ClientResource/Pages/ListClients.php app/Filament/Resources/CRoleResource/Concern/CanAccessClientData.php
git commit -m "feat: add AdminInstansi to client data access scoping"
```

---

### Task 6: Verify no system-admin resources are accessible

**Step 1: Confirm exclusions**

Verify these files do NOT reference `SystemRole::AdminInstansi`:

- `app/Filament/Resources/AdminAccessResource.php` — SuperAdmin only, no change needed
- `app/Filament/Resources/VerifierAccessResource.php` — SuperAdmin only, no change needed
- `app/Filament/Resources/UserResource.php` — Shield-managed, no change needed
- `app/Filament/Resources/Shield/RoleResource.php` — Shield-managed, no change needed
- `app/Filament/Resources/ClientDocumentTypeResource.php` — Shield-managed, no change needed

**Step 2: Search for any missed references**

```bash
grep -rn "AdminInstansi" app/Filament/Resources/AdminAccessResource.php app/Filament/Resources/VerifierAccessResource.php
```

Should return nothing.

**Step 3: Final commit (if any cleanup needed)**

```bash
git add -A
git commit --allow-empty -m "chore: verify AdminInstansi role migration complete"
```