# User Resource Client Sync Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Sync Client (NIP + jabatan) when User Resource create/edit includes the `client` role; hide Profil Saya unless `isActiveClient()`.

**Architecture:** Reorder `UserResource` form (roles live → conditional NIP/jabatan). Create/Edit pages sync Client after save via shared helper. Tighten `ClientProfilePage` (and other Profil Saya items that always show) to `isActiveClient()`.

**Tech Stack:** Laravel 12, Filament v3, Spatie Permission, PHPUnit 11

## Global Constraints

- Spec: `docs/superpowers/specs/2026-08-27-user-resource-client-sync-design.md`
- Roles include `client` (any combo) → require NIP + Jabatan; create/update Client
- No `client` role → hide fields; never delete existing Client
- Same rules on create and edit
- No new migrations/columns
- Profil Saya / Identitas nav only when `isActiveClient()`
- Git author via env (no `git config`): Frans Filasta Pratama / fransfilastap@live.com
- Work on branch `feat/user-resource-client-sync` (not commit feature code directly on main)

---

## File structure

| File | Responsibility |
|------|----------------|
| `app/Filament/Resources/UserResource.php` | Form order, live roles, conditional NIP/jabatan, helpers, email unique, fix pagination default |
| `app/Filament/Resources/UserResource/Pages/CreateUser.php` | After create sync Client |
| `app/Filament/Resources/UserResource/Pages/EditUser.php` | Fill NIP/jabatan from Client; after save sync |
| `app/Filament/Pages/Client/ClientProfilePage.php` | `shouldRegisterNavigation` → `isActiveClient()` |
| `tests/Feature/Filament/UserResourceClientSyncTest.php` | Create/edit/nav coverage |

---

### Task 1: Form + Client sync helper + Create/Edit

**Files:** UserResource, CreateUser, EditUser, test file

- [ ] Failing tests for create with client → Client exists; create without client → no Client; edit add client → create Client; edit remove client → Client remains
- [ ] Helpers on UserResource: `rolesIncludeClient(?array $roleIds): bool`, `syncClientForUser(User $user, array $data): void`
- [ ] Form: roles first (`live`), then NIP/jabatan (visible/required when client), then general, then verification
- [ ] NIP/jabatan not User columns — use form state; dehydrate only when needed or handle entirely in afterCreate/afterSave from `$this->form->getState()` / raw component state
- [ ] CreateUser `afterCreate`; EditUser `mutateFormDataBeforeFill` + `afterSave`
- [ ] Email `unique(User::class)`; NIP unique on clients ignoring current client on edit
- [ ] Fix `defaultPaginationPageOption(10)`
- [ ] Commit

### Task 2: Profil Saya Identitas visibility

- [ ] Failing test: SuperAdmin without client → `ClientProfilePage::shouldRegisterNavigation()` false; active client → true
- [ ] Change `shouldRegisterNavigation` to `auth()->user()?->isActiveClient() ?? false`
- [ ] Commit

### Task 3: Sanity

- [ ] Run UserResourceClientSyncTest + ClientMenuVerificationGateTest (Identitas assertion still holds for active clients)
- [ ] Commit only if fixes

---

## Notes for implementer

- Spatie relationship select stores **role IDs**; resolve `client` by `Role::where('name', SystemRole::Client->value)->whereIn('id', $ids)->exists()`.
- Jabatan select: `CRole::query()` options `id` / `role_name` (match Register / Client forms).
- `Client::updateOrCreate(['user_id' => $user->id], ['nip' => ..., 'c_role_id' => ...])` when syncing; observer may fill master data.
- Do not put `nip`/`c_role_id` into User `create()` attributes — strip in `mutateFormDataBeforeCreate` / `mutateFormDataBeforeSave`.
