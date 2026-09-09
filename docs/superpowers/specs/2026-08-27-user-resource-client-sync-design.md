# User Resource Client Sync + Profil Saya Visibility

**Date:** 2026-08-27  
**Status:** Approved for planning  
**Area:** Filament `UserResource` create/edit; Profil Saya navigation visibility  
**Related:** Public register creates User+Client; admin User Resource currently creates User only

## Goal

When creating or editing a user in User Resource:

1. If roles **include** `client` (alone or with admin/verifier/etc.) → require **NIP** + **Jabatan** (`c_role_id`) and sync a linked `Client` (create if missing, update if present).
2. If roles do **not** include `client` → hide those fields; do not create a Client; if a Client already exists, **keep** it.
3. Show **Profil Saya** (including Identitas) only for **active clients**: `client` role **and** a linked Client record.

No new database columns.

## Context

- Public `Register::handleRegistration()` already creates `User`, assigns `client`, and creates `Client`.
- `UserResource` form today: name, email, password, roles, email_verified_at — no Client fields.
- `User::isActiveClient()` = `hasSystemRole(Client) && client !== null`.
- `User::canAccessPanel()` already allows admin/verifier/SuperAdmin without a Client.
- `ClientProfilePage::shouldRegisterNavigation()` currently returns `true` always — Identitas can appear for non-clients.
- `clients.nip` / `clients.c_role_id` exist; agency and other fields are nullable enough for a minimal Client create. `ClientObserver` may enrich from Master JF by NIP.

## Rules

### Form

Order:

1. **Roles** (multiple, `live()`)
2. **NIP** + **Jabatan** — visible and required only when selected role IDs/names include `client`
3. General: name, email, password
4. Verification: `email_verified_at` (unchanged)

Detection: selected roles include Spatie role name `client` (`SystemRole::Client->value`).

### Create / Edit sync

| Condition | Behavior |
|-----------|----------|
| Roles include `client`, no Client yet | Create `Client` with `user_id`, `nip`, `c_role_id` (other fields null / observer defaults) |
| Roles include `client`, Client exists | Update that Client’s `nip` and `c_role_id` |
| Roles do not include `client` | Do not create Client; do not delete existing Client |

Apply the **same** rules on create and edit.

### Profil Saya visibility

- Identitas and other client-menu items should only register for `auth()->user()?->isActiveClient()`.
- Non-client staff (admin/verifier/SuperAdmin only) access the panel but do not see Profil Saya.
- User with leftover Client row but without `client` role: Profil Saya hidden.

### Validation

- NIP unique on `clients.nip` (ignore current user’s client on edit).
- Jabatan required when client role selected (`exists` on `c_roles`).
- Email unique on `users` (recommended fix while touching the form).

## Approach

**Chosen:** Extend `UserResource` form + Create/Edit page hooks (mutate/after-save) to sync Client. Tighten navigation with `isActiveClient()`.

Rejected: separate ASN wizard; role-observer-only Client create (harder to collect NIP/jabatan in-form).

## Architecture

```text
UserResource form
  roles (live)
       │
       ├── includes client? ──yes──► show NIP + Jabatan (required)
       └── no ────────────────────► hide NIP + Jabatan
              │
              ▼
  save User + role relationships
              │
              ▼
  includes client?
       ├── yes → Client::updateOrCreate by user_id { nip, c_role_id }
       └── no  → leave Client untouched

Navigation (Profil Saya / Identitas)
  shouldRegisterNavigation → user->isActiveClient()
```

## Non-goals

- Changing public Register flow.
- Deleting Client when `client` role is removed.
- Requiring agency on User Resource create.
- New migrations / new columns.

## Testing

1. Create user with only admin/verifier → no Client; no Profil Saya when logged in as that user.
2. Create user with `client` (+ optional admin) + NIP/jabatan → Client created and linked.
3. Edit: add `client` to existing user → Client created with NIP/jabatan.
4. Edit: remove `client` → Client row remains; Profil Saya hidden.
5. Edit: change NIP/jabatan while still client → Client updated.
6. Identitas nav hidden for SuperAdmin without client role/record.
