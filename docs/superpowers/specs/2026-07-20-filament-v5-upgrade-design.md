# Filament v3 → v5 Upgrade Design

**Date:** 2026-07-20  
**Status:** Approved  
**Current:** Filament `v3.3.54`, Livewire `v3.8.2`  
**Target:** Filament `^5.0` + Livewire `^4.0`

## Goal

Upgrade the admin panel stack to the latest Filament PHP (v5) without removing third-party plugins, with a controlled two-PR delivery and full regression coverage before each merge.

## Decisions

| Topic | Choice |
|-------|--------|
| Plugin policy | Keep all plugins; only proceed with versions that support the Filament major in that PR |
| Delivery | Two PRs: `v3 → v4`, then `v4 → v5` |
| Directory layout | Migrate to official Filament v4 resource/cluster directory structure in PR1 |
| Verification | Expand Filament Livewire PHPUnit coverage substantially + manual checklist |
| Approach | Official Filament upgrade scripts + required guide fixes; no optional API modernizations |

## Scope

### In scope

- Filament core upgrade via official `filament/upgrade` scripts
- Livewire 4 (PR2 only)
- Plugin major bumps in lockstep with Filament:
  - `bezhansalleh/filament-shield`
  - `hugomyb/filament-media-action`
  - `tapp/filament-invite`
  - `valentin-morice/filament-json-column`
  - `kenepa/banner`
- `filament:upgrade-directory-structure-to-v4` during PR1
- Custom theme CSS updates (`resources/css/filament/admin/`)
- `AdminPanelProvider` and custom Filament Pages / Resources / Clusters / Widgets / Exports / Actions
- Custom Livewire used by Filament flows (`ClientActivityTable`, `ClientEducationInfolist`, etc.)
- Expanded automated tests and a manual regression checklist

### Out of scope

- Removing or replacing plugins
- Optional refactors (schema extraction for style, unrelated cleanup)
- Non-Filament framework upgrades beyond what Filament/Livewire require
- Changing product behavior except where required by breaking API changes

## Architecture & delivery

```text
main
  └─ upgrade/filament-v4   (PR1: Filament 4 + plugin majors + dir migrate + tests)
        └─ merge
              └─ upgrade/filament-v5   (PR2: Filament 5 + Livewire 4 + plugin alignment + retest)
```

| Phase | Target | Risk profile |
|-------|--------|--------------|
| PR1 | Filament **v4** | High — API/directory/theme/plugin rewrites |
| PR2 | Filament **v5** + Livewire **4** | Lower — Filament APIs largely unchanged from v4; Livewire 4 is the main delta |

**Environment already satisfied:** PHP 8.4, Laravel 12, Tailwind CSS 4.

## PR1 — Filament v3 → v4

### Dependencies

1. `composer require filament/upgrade:"^4.0" -W --dev`
2. `vendor/bin/filament-v4` (review all generated changes)
3. Composer-require Filament `^4.0` and plugin lines compatible with Filament 4, then `composer update`
4. Expected plugin targets (confirm at implementation time against Packagist):
   - Shield `^4.0` (complete rewrite — follow Shield upgrade guide)
   - Media Action latest Filament 4/5 line
   - Invite `^2.0`
   - JSON Column Filament-4 line (likely `^3.0`)
   - Banner `^1.0`
5. `composer remove filament/upgrade --dev`

### Code & structure

1. Dry-run then apply `php artisan filament:upgrade-directory-structure-to-v4`
2. Fix same-namespace references the migrator cannot rewrite
3. Apply remaining manual items from the Filament v4 upgrade guide (panels, forms, tables, custom pages/actions, exporters)
4. Publish `config/filament.php` only when needed; prefer v4 defaults unless preserving a specific v3 behavior is required

### Theme & assets

1. Update `resources/css/filament/admin/theme.css` for v4 (`@source` paths for Filament and Banner)
2. Remove obsolete Tailwind content paths from `tailwind.config.js` where the v4/plugin guides require it
3. Rebuild assets via Sail (`vendor/bin/sail bun run build`)
4. Keep custom auth views and render hooks working

### Panel wiring

1. Update `AdminPanelProvider` for Shield v4 and Banner v1 APIs
2. Re-verify discover paths for Resources, Pages, Clusters, and Widgets after moves

## PR2 — Filament v4 → v5

1. `composer require filament/upgrade:"^5.0" -W --dev`
2. `vendor/bin/filament-v5`
3. Require Filament `^5.0` (brings Livewire 4) and align any plugins that use a separate Filament-5 major (e.g. JSON Column `^4.0` if still on the Filament-4 line)
4. Apply Livewire 4 upgrade notes to custom Livewire components
5. Rebuild theme/assets; re-smoke panel
6. `composer remove filament/upgrade --dev`

PR2 does not start until PR1 is merged and green.

## Testing & acceptance

### Automated (expand substantially; suite is currently minimal)

Cover with PHPUnit + Livewire/Filament testing helpers:

- Auth: login, register, email verification prompt wiring
- Dashboard load for authenticated roles
- Representative Resources: list/search/create/edit (or view) happy paths
- Custom Pages: client profile / basic identity, verification workspace actions (accept/reject)
- Shield: role resource access and permission-gated pages
- Invite action on user flows
- Banner manager access permission
- MediaAction / JSON column usage sites where present
- Exporters smoke where practical

### Manual checklist (both PRs)

- Login / register / profile
- Navigation groups and discovery after directory moves
- Key client management flows
- Verification accept/reject
- Exports and database notifications
- Theme (default light mode)
- Plugin UIs: Shield, Banner, Invite, Media, JSON column

### Merge gates

- PR1: automated suite green + manual checklist complete
- PR2: same suite re-run (plus Livewire-4-sensitive cases) + manual checklist complete

## Error handling

- If Composer cannot resolve a plugin with Filament: **stop** and fix version constraints — do not remove the plugin
- If the directory migrator leaves broken imports: fix via failing tests / static analysis before merge
- Prefer repairing upgrade fallout over rewriting domain logic
- Keep PR1 and PR2 failure modes separable (do not combine Filament 5 into PR1)

## Non-goals / explicit deferrals

- Hybrid “keep v3 file generation flags” layout (rejected)
- Single big-bang PR to v5 (rejected)
- Dropping incompatible plugins temporarily (rejected)

## Success criteria

1. Application runs on Filament v5 and Livewire 4
2. All five plugins remain installed and functional
3. Resources/clusters use the official v4+ directory structure
4. Expanded Filament regression tests pass on both PR1 and PR2 targets
5. Manual checklist signed off for each PR

