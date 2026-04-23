# AdminInstansi Role Design

**Date:** 2026-04-25
**Status:** Approved

## Problem

A new role is needed for admins who manage only their own instansi's clients. The existing `admin` role has access to system administration resources (users, roles, admin access, verifier access, system settings) that instansi-level admins should not see.

## Solution

Add `AdminInstansi` as a new `SystemRole` enum case. It gets client-facing resource access (scoped by `AdminAccess`) but is excluded from system-admin resources.

## New Enum Value

```php
// app/Enums/SystemRole.php
case AdminInstansi = 'admin-instansi';
```

## Permission Matrix

| Resource | SuperAdmin | Admin | AdminInstansi | Verifier | Client |
|---|---|---|---|---|---|
| Client CRUD | Yes (all) | Yes (scoped) | Yes (scoped) | View only | Own only |
| Activity Reports | Yes | Yes (scoped) | Yes (scoped) | Yes (scoped) | No |
| Point Submissions | Yes | Yes (scoped) | Yes (scoped) | Yes (scoped) | Own only |
| Activity Verification | Yes | Yes | Yes (scoped) | Yes (scoped) | No |
| Point Verification | Yes | Yes | No | Yes (scoped) | No |
| User Management | Yes | No | No | No | No |
| Role Management | Yes | No | No | No | No |
| Admin Access | Yes | No | No | No | No |
| Verifier Access | Yes | No | No | No | No |
| Client Document Types | Yes | No | No | No | No |
| Dashboard (admin view) | Yes | Yes | Yes (scoped) | Yes (scoped) | No (client view) |
| Dashboard (client view) | No | No | No | No | Yes |

## Files to Modify

### Add enum value
- `app/Enums/SystemRole.php` — add `AdminInstansi = 'admin-instansi'`

### Add to policy/viewAny groups (CAN access)
- `app/Policies/ClientActivityPolicy.php` — add `SystemRole::AdminInstansi` to viewAny, view, create, update groups
- `app/Policies/ActivityReportPolicy.php` — add `SystemRole::AdminInstansi` to viewAny, view groups
- `app/Filament/Resources/ActivityReportResource.php` — add to shouldRegisterNavigation, canViewAny
- `app/Filament/Widgets/ClientsByRoleChart.php` — add to admin branch
- `app/Filament/Widgets/ClientNumbersOverview.php` — add to admin branch
- `app/Filament/Widgets/ClientNumbersByRoleLevelOverview.php` — add to admin branch
- `app/Filament/Widgets/ClientNumbersByGradeOverview.php` — add to admin branch
- `app/Filament/Widgets/ClientNumbersByStatusOverview.php` — add to admin branch
- `app/Filament/Resources/ClientResource/Pages/ListClients.php` — add to admin scoping
- `app/Filament/Resources/CRoleResource/Concern/CanAccessClientData.php` — add to admin branch

### Already excluded (no changes needed)
- `app/Filament/Resources/AdminAccessResource.php` — restricted to SuperAdmin only
- `app/Filament/Resources/VerifierAccessResource.php` — restricted to SuperAdmin only
- `app/Filament/Resources/Shield/RoleResource.php` — restricted by Shield
- `app/Filament/Resources/UserResource.php` — restricted by Shield
- `app/Filament/Resources/ClientDocumentTypeResource.php` — restricted by Shield

### Data scoping
- No changes to `AdminAccess` model or resource — the existing pivot table mechanism already handles instansi scoping. Assigning an `AdminInstansi` user an `AdminAccess` row with their instansi's `c_role_id` and entity scope limits their data visibility.

## Seeder Update

- `database/seeders/RoleSeeder.php` — add `SystemRole::AdminInstansi->value` to the seeder

## Not in Scope

- Verification workspace access (AdminInstansi cannot verify — that's for Verifier role)
- System settings management
- User/role management