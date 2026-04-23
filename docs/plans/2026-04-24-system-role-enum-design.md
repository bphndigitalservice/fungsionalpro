# SystemRole Enum Design

**Date:** 2026-04-24
**Status:** Approved

## Problem

Spatie permission role name strings (`super_admin`, `admin`, `verifier`, etc.) are hardcoded across 48+ call sites. A typo like `'admin-reginal'` would silently break authorization. Renaming a role requires find-and-replace across the entire codebase with no compile-time safety.

## Solution

Create a `SystemRole` backed string enum that mirrors the Spatie role names stored in the database. This provides a single source of truth for role string references, eliminating typos and making renames safe.

## What It Is

```php
// app/Enums/SystemRole.php
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

The enum value matches the exact string stored in the `roles.name` column via Spatie Permission. No database migration needed.

## What Changes

### User model helpers

Add convenience methods to avoid `->value` verbosity:

```php
// app/Models/User.php
public function hasSystemRole(SystemRole $role): bool
{
    return $this->hasRole($role->value);
}

public function hasAnySystemRole(SystemRole ...$roles): bool
{
    return $this->hasRole(array_map(fn ($r) => $r->value, $roles));
}
```

### Call site migration pattern

Before:
```php
$user->hasRole(['admin', 'super_admin'])
$user->hasRole('verifier')
$this->user->assignRole(['client', 'panel_user'])
User::role(['admin'])
User::role(['verifier', 'admin-regional'])
```

After:
```php
$user->hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin)
$user->hasSystemRole(SystemRole::Verifier)
$this->user->assignRole([SystemRole::Client->value, SystemRole::PanelUser->value])
User::role([SystemRole::Admin->value])
User::role([SystemRole::Verifier->value, SystemRole::AdminRegional->value])
```

Note: `assignRole()` and `User::role()` Eloquent scope take raw strings, so they use `->value`. The new helpers `hasSystemRole()` and `hasAnySystemRole()` wrap `hasRole()` for cleaner call sites.

### Files touched

~48 `hasRole()` call sites, 2 `User::role()` calls, 1 `assignRole()` call, 1 seeder, 2 config references.

### Files NOT touched

- CRole, CRoleLevel, Client model — separate domain concept (jabatan fungsional)
- `config/filament-shield.php` — uses its own string convention
- Database migrations — no schema changes
- Spatie package internals

## What It Does NOT Do

- No new authorization logic or gates
- No changes to how Spatie stores roles
- No database migration
- No changes to CRole (domain concept, separate system)
- `pre-client` role intentionally excluded from the enum (seeded but never referenced in code)

## Rationale

- Minimum viable change for type safety
- No runtime behavior change — enum values match existing strings exactly
- Compile-time safety: typos cause PHP errors instead of silent auth bypass
- Single point of change for role name strings
- Easy to search: `SystemRole::` surfaces every role reference instantly