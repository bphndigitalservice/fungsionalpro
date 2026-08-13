# Admin Access Region by System Role

**Date:** 2026-08-13  
**Status:** Approved for planning  
**Resource:** `AdminAccessResource`

## Goal

Make Akses Admin require a regional entity only when the selected user is `admin-instansi`. When the selected user is `admin`, omit region so access is global for that jabatan.

## Context

- `admin_accesses` stores `user_id`, `c_role_id`, and optional polymorphic region (`entity_type`, `entity_id`).
- `ClientAccessService` already treats a null region as every agency for that jabatan, and a set region as that agency only. Both `admin` and `admin-instansi` use this table.
- The Akses Admin form currently picks the user by **nama**, but the dropdown only includes `admin` users. Region (`accessible`) is always shown and is not required.
- Verifier access is unchanged. It still always stores a region.

## Approach

**Approach 1 (chosen):** Keep one form. Nama still selects the user. After a user is chosen, look up that user’s system role:

- `admin` → hide region and save `entity_type` / `entity_id` as null (global).
- `admin-instansi` → show region and require it.

Expand the nama dropdown to include both roles. No schema change. Leave the `entity_id is null` query branch in `ClientAccessService`.

Rejected alternatives:

- Require region on every admin-access row (NOT NULL) — would remove global `admin` access.
- Form required only, same rule for every user — does not distinguish `admin` vs `admin-instansi`.
- Split into two Filament resources — more UI than needed.

## Architecture

```text
Akses Admin form
  user_id (nama, live)
       │
       ▼
  User system role?
       ├── admin            → hide accessible; save entity_* = null (global)
       └── admin-instansi   → show accessible; required
              │
              ▼
  admin_accesses (entity_type/entity_id still nullable)
              │
              ▼
  ClientAccessService (unchanged)
       null entity_id  → all agencies for c_role_id
       set entity_*    → match client agency
```

## Form behavior

### Nama (`user_id`)

- Keep `Select` labeled as the user name field.
- Options: users with system role `admin` **or** `admin-instansi` (`User::role([SystemRole::Admin->value, SystemRole::AdminInstansi->value])`).
- `searchable()`, `preload()`, `required()`, `live()` so region visibility updates when nama changes.

### Jabatan (`c_role_id`)

- Unchanged. Still required. This is jabatan fungsional, not the system role.

### Region (`accessible`)

- Same MorphToSelect types as today: `RegDepartment`, `RegProvince`, `RegRegency`.
- **Visible and required** when the selected user has `admin-instansi` and does **not** have `admin`.
- **Hidden** when the selected user has `admin` (including a user who also has `admin-instansi`).
- Hidden while no user is selected.

### Save

On create and edit, if the selected user is treated as `admin` (global):

- Force `entity_type` and `entity_id` to null.

That clears a leftover region if an existing row is reassigned to an `admin` user.

## Dual-role rule

If one user has both `admin` and `admin-instansi`, treat them as `admin`: region hidden, saved null, global access.

## Data model

- No migration. `entity_type` / `entity_id` stay nullable.
- `AdminAccess` model and `accessible()` morph stay as they are.
- `ClientAccessService` nationwide (`whereNull('aa.entity_id')`) branch stays as a fallback.

## Table

- No required change. `accessible.name` (Ruang Regional) stays empty for global `admin` rows and shows the agency name for `admin-instansi` rows.

## Edge cases

- Switching nama from `admin-instansi` to `admin` on edit: region is cleared on save.
- Switching nama from `admin` to `admin-instansi`: region appears and must be filled before save.
- Existing `admin` rows with a region already stored: unchanged until edited; saving as `admin` then clears region.
- Existing `admin-instansi` rows with no region: editing requires a region before save. Listing/scoping still treats null as global until that edit (fallback left in place).
- Super Admin remains the only role that can open Akses Admin.

## Out of scope

- Verifier access form or `verifier_accesses` schema.
- Making `admin_accesses.entity_*` NOT NULL.
- Removing the null-region branch in `ClientAccessService`.
- Master JF region scoping (`MasterJfResource` still uses `c_role_id` only).
- Changing which system roles `ClientAccessService` maps onto `admin_accesses`.

## Testing

Follow `MasterJfEditFormTest` Livewire/Filament patterns. Act as Super Admin (the resource is Super Admin–only).

1. Nama options include an `admin` user and an `admin-instansi` user, and exclude a user with neither role.
2. Selecting an `admin` user and saving with jabatan, without region, persists `entity_type` / `entity_id` as null.
3. Selecting an `admin-instansi` user without region fails validation on `accessible`.
4. Selecting an `admin-instansi` user with a department/province/regency persists that morph pair.
5. Editing a row from an `admin-instansi` user with a region to an `admin` user clears `entity_type` / `entity_id`.

## Success criteria

- Super Admin can assign Akses Admin to both `admin` and `admin-instansi` users by nama.
- `admin` assignments are global for the chosen jabatan (no region).
- `admin-instansi` assignments cannot be saved without a region.
- Client scoping behavior for existing rows does not change until those rows are edited.
