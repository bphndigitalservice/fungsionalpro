# Ukom Verification Tabs & Pembina Visibility Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add All / New / Processed tabs to Verifikasi Pengajuan Ukom and hide undelivered (and instansi-only rejected) pengajuan from admin pembina.

**Architecture:** Keep `UkomApplicationResource` / `ListUkomApplications`. Tighten `UkomApplicationAccess::scopedQuery` for pembina, and add Filament list tabs whose filters are role-aware helpers on the same access service (shared with badges).

**Tech Stack:** Laravel 12, Filament v3 (`ListRecords` + `Filament\Resources\Components\Tab`), Livewire 3, PHPUnit 11, `RefreshDatabase`

## Global Constraints

- Spec: `docs/superpowers/specs/2026-09-08-ukom-verification-tabs-design.md`
- Pembina visibility: `status = pending_admin OR admin_reviewed_at IS NOT NULL` (never `pending_instansi`; never instansi-only `rejected`)
- SuperAdmin: all non-draft; tabs New = open pipeline, Processed = terminal
- Admin-instansi-only: existing AdminAccess scope unchanged; New = `pending_instansi`
- Dual-role `admin` + `admin-instansi` = pembina visibility and tabs
- Default active tab = `new`; badge only on New
- No workspace page rebuild; no status-machine / accept→Client / email changes
- PHPUnit (not Pest); follow `tests/Feature/Ukom/PengajuanUkomFlowTest.php`

---

## File structure

| File | Responsibility |
| --- | --- |
| `app/Services/UkomApplicationAccess.php` | Pembina scope + `applyNewTabFilter` / `applyProcessedTabFilter` helpers |
| `app/Filament/Resources/UkomApplicationResource/Pages/ListUkomApplications.php` | `getTabs()`, default `new`, New badge |
| `database/factories/UkomApplicationFactory.php` | Optional states for instansi-rejected / pembina-decided rows |
| `tests/Feature/Ukom/PengajuanUkomFlowTest.php` | Visibility + tab coverage; fix tests that assumed pembina sees `pending_instansi` |

---

### Task 1: Pembina `scopedQuery` visibility

**Files:**
- Modify: `app/Services/UkomApplicationAccess.php`
- Modify: `database/factories/UkomApplicationFactory.php`
- Modify: `tests/Feature/Ukom/PengajuanUkomFlowTest.php`
- Test: `tests/Feature/Ukom/PengajuanUkomFlowTest.php`

**Interfaces:**
- Consumes: `UkomApplicationStatus`, existing `isInstansiOnly()`, `isSuperAdmin()`
- Produces: `scopedQuery(User $user): Builder` with pembina filter; factory states `rejectedAtInstansi()` and `decidedByPembina()` (accepted or rejected with `admin_reviewed_at`)

- [ ] **Step 1: Add factory states for review markers**

In `database/factories/UkomApplicationFactory.php`, add:

```php
public function rejectedAtInstansi(): static
{
    return $this->state(fn () => [
        'status' => UkomApplicationStatus::Rejected,
        'rejection_reason' => 'Ditolak instansi',
        'instansi_reviewed_by' => User::factory(),
        'instansi_reviewed_at' => now(),
        'admin_reviewed_by' => null,
        'admin_reviewed_at' => null,
    ]);
}

public function acceptedByPembina(): static
{
    return $this->state(fn () => [
        'status' => UkomApplicationStatus::Accepted,
        'instansi_reviewed_by' => User::factory(),
        'instansi_reviewed_at' => now()->subDay(),
        'admin_reviewed_by' => User::factory(),
        'admin_reviewed_at' => now(),
        'rejection_reason' => null,
    ]);
}

public function rejectedByPembina(): static
{
    return $this->state(fn () => [
        'status' => UkomApplicationStatus::Rejected,
        'rejection_reason' => 'Ditolak pembina',
        'instansi_reviewed_by' => User::factory(),
        'instansi_reviewed_at' => now()->subDay(),
        'admin_reviewed_by' => User::factory(),
        'admin_reviewed_at' => now(),
    ]);
}
```

- [ ] **Step 2: Write failing visibility tests**

Append to `tests/Feature/Ukom/PengajuanUkomFlowTest.php`:

```php
public function test_pembina_scoped_query_hides_undelivered_and_instansi_rejects(): void
{
    $calon = $this->makeCalonUser();
    $base = [
        'user_id' => $calon->id,
        'target_c_role_id' => $this->ah->id,
        'type' => ClientCluster::Central,
        'agency_type' => RegDepartment::class,
        'agency_id' => $this->department->id,
    ];

    $pendingInstansi = UkomApplication::factory()->pendingInstansi()->create($base);
    $pendingAdmin = UkomApplication::factory()->pendingAdmin()->create($base);
    $instansiReject = UkomApplication::factory()->rejectedAtInstansi()->create($base);
    $accepted = UkomApplication::factory()->acceptedByPembina()->create($base);

    $pembina = User::factory()->create();
    $pembina->assignRole(SystemRole::Admin->value);

    $ids = app(UkomApplicationAccess::class)
        ->scopedQuery($pembina)
        ->pluck('id')
        ->all();

    $this->assertNotContains($pendingInstansi->id, $ids);
    $this->assertNotContains($instansiReject->id, $ids);
    $this->assertContains($pendingAdmin->id, $ids);
    $this->assertContains($accepted->id, $ids);
}

public function test_super_admin_scoped_query_sees_pending_instansi(): void
{
    $calon = $this->makeCalonUser();
    $pendingInstansi = UkomApplication::factory()->pendingInstansi()->create([
        'user_id' => $calon->id,
        'target_c_role_id' => $this->ah->id,
        'agency_type' => RegDepartment::class,
        'agency_id' => $this->department->id,
    ]);

    $super = User::factory()->create();
    $super->assignRole(SystemRole::SuperAdmin->value);

    $this->assertTrue(
        app(UkomApplicationAccess::class)
            ->scopedQuery($super)
            ->whereKey($pendingInstansi->id)
            ->exists()
    );
}

public function test_dual_role_follows_pembina_visibility(): void
{
    $calon = $this->makeCalonUser();
    $pendingInstansi = UkomApplication::factory()->pendingInstansi()->create([
        'user_id' => $calon->id,
        'target_c_role_id' => $this->ah->id,
        'agency_type' => RegDepartment::class,
        'agency_id' => $this->department->id,
    ]);

    $both = User::factory()->create();
    $both->assignRole([SystemRole::Admin->value, SystemRole::AdminInstansi->value]);
    AdminAccess::create([
        'user_id' => $both->id,
        'c_role_id' => $this->ah->id,
        'entity_type' => RegDepartment::class,
        'entity_id' => $this->department->id,
    ]);

    $this->assertFalse(
        app(UkomApplicationAccess::class)
            ->scopedQuery($both)
            ->whereKey($pendingInstansi->id)
            ->exists()
    );
}
```

- [ ] **Step 3: Run tests to verify they fail**

Run:

```bash
php artisan test --filter=test_pembina_scoped_query_hides_undelivered_and_instansi_rejects
php artisan test --filter=test_super_admin_scoped_query_sees_pending_instansi
php artisan test --filter=test_dual_role_follows_pembina_visibility
```

Expected: first and third FAIL (pembina currently sees all non-draft); second may PASS already.

- [ ] **Step 4: Implement pembina filter in `scopedQuery`**

In `app/Services/UkomApplicationAccess.php`, change the admin branch from returning `$query` unchanged to:

```php
if ($user->hasSystemRole(SystemRole::Admin) && ! $this->isInstansiOnly($user)) {
    return $query->where(function (Builder $reached): void {
        $reached->where('status', UkomApplicationStatus::PendingAdmin->value)
            ->orWhereNotNull('admin_reviewed_at');
    });
}
```

Leave SuperAdmin and admin-instansi branches unchanged.

- [ ] **Step 5: Fix existing tests that assumed pembina sees `pending_instansi`**

In `PengajuanUkomFlowTest.php`:

1. `test_verification_table_shows_instansi_and_submitted_at` — create with `pendingAdmin()` (or `->pendingAdmin()->create([...])`) so pembina can see the row; keep asserting Instansi / Diajukan Pada / agency name.
2. `test_verification_view_opens_berkas_with_icon` — same: use `pendingAdmin()` so `canView` / scoped query allows the view.

- [ ] **Step 6: Run visibility-related tests**

Run:

```bash
php artisan test tests/Feature/Ukom/PengajuanUkomFlowTest.php
```

Expected: PASS for all tests in that file (tabs not added yet; list still defaults without tab filters).

- [ ] **Step 7: Commit**

```bash
git add app/Services/UkomApplicationAccess.php database/factories/UkomApplicationFactory.php tests/Feature/Ukom/PengajuanUkomFlowTest.php
git commit -m "fix(ukom): hide undelivered pengajuan from admin pembina"
```

---

### Task 2: List tabs (All / New / Processed)

**Files:**
- Modify: `app/Services/UkomApplicationAccess.php`
- Modify: `app/Filament/Resources/UkomApplicationResource/Pages/ListUkomApplications.php`
- Modify: `tests/Feature/Ukom/PengajuanUkomFlowTest.php`

**Interfaces:**
- Consumes: `scopedQuery`, `isInstansiOnly`, `isSuperAdmin`
- Produces:
  - `applyNewTabFilter(Builder $query, User $user): Builder`
  - `applyProcessedTabFilter(Builder $query, User $user): Builder`
  - `ListUkomApplications::getTabs(): array`
  - `ListUkomApplications::getDefaultActiveTab(): string` → `'new'`

- [ ] **Step 1: Write failing tab tests**

Append to `tests/Feature/Ukom/PengajuanUkomFlowTest.php`:

```php
public function test_pembina_list_tabs_separate_new_and_processed(): void
{
    $calon = $this->makeCalonUser();
    $base = [
        'user_id' => $calon->id,
        'target_c_role_id' => $this->ah->id,
        'type' => ClientCluster::Central,
        'agency_type' => RegDepartment::class,
        'agency_id' => $this->department->id,
    ];

    $newRow = UkomApplication::factory()->pendingAdmin()->create($base);
    $processed = UkomApplication::factory()->acceptedByPembina()->create($base);
    $hidden = UkomApplication::factory()->pendingInstansi()->create($base);

    $pembina = User::factory()->create();
    $pembina->assignRole(SystemRole::Admin->value);
    $this->actingAs($pembina);

    Livewire::test(ListUkomApplications::class)
        ->assertSet('activeTab', 'new')
        ->assertCanSeeTableRecords([$newRow])
        ->assertCanNotSeeTableRecords([$processed, $hidden])
        ->set('activeTab', 'processed')
        ->assertCanSeeTableRecords([$processed])
        ->assertCanNotSeeTableRecords([$newRow, $hidden])
        ->set('activeTab', 'all')
        ->assertCanSeeTableRecords([$newRow, $processed])
        ->assertCanNotSeeTableRecords([$hidden]);
}

public function test_admin_instansi_list_new_tab_shows_pending_instansi_only(): void
{
    $calon = $this->makeCalonUser();
    $base = [
        'user_id' => $calon->id,
        'target_c_role_id' => $this->ah->id,
        'type' => ClientCluster::Central,
        'agency_type' => RegDepartment::class,
        'agency_id' => $this->department->id,
    ];

    $newRow = UkomApplication::factory()->pendingInstansi()->create($base);
    $forwarded = UkomApplication::factory()->pendingAdmin()->create(array_merge($base, [
        'instansi_reviewed_at' => now(),
    ]));

    $instansi = User::factory()->create();
    $instansi->assignRole(SystemRole::AdminInstansi->value);
    AdminAccess::create([
        'user_id' => $instansi->id,
        'c_role_id' => $this->ah->id,
        'entity_type' => RegDepartment::class,
        'entity_id' => $this->department->id,
    ]);
    $this->actingAs($instansi);

    Livewire::test(ListUkomApplications::class)
        ->assertSet('activeTab', 'new')
        ->assertCanSeeTableRecords([$newRow])
        ->assertCanNotSeeTableRecords([$forwarded])
        ->set('activeTab', 'processed')
        ->assertCanSeeTableRecords([$forwarded])
        ->assertCanNotSeeTableRecords([$newRow]);
}
```

- [ ] **Step 2: Run tab tests to verify they fail**

Run:

```bash
php artisan test --filter=test_pembina_list_tabs_separate_new_and_processed
php artisan test --filter=test_admin_instansi_list_new_tab_shows_pending_instansi_only
```

Expected: FAIL (no tabs / `activeTab` not `new`, or no filter).

- [ ] **Step 3: Add tab filter helpers on `UkomApplicationAccess`**

```php
public function applyNewTabFilter(Builder $query, User $user): Builder
{
    if ($user->isSuperAdmin()) {
        return $query->whereIn('status', [
            UkomApplicationStatus::PendingInstansi->value,
            UkomApplicationStatus::PendingAdmin->value,
        ]);
    }

    if ($this->isInstansiOnly($user)) {
        return $query->where('status', UkomApplicationStatus::PendingInstansi->value);
    }

    return $query->where('status', UkomApplicationStatus::PendingAdmin->value);
}

public function applyProcessedTabFilter(Builder $query, User $user): Builder
{
    if ($user->isSuperAdmin()) {
        return $query->whereIn('status', [
            UkomApplicationStatus::Accepted->value,
            UkomApplicationStatus::Rejected->value,
        ]);
    }

    if ($this->isInstansiOnly($user)) {
        return $query->whereIn('status', [
            UkomApplicationStatus::PendingAdmin->value,
            UkomApplicationStatus::Accepted->value,
            UkomApplicationStatus::Rejected->value,
        ]);
    }

    return $query->whereNotNull('admin_reviewed_at');
}
```

- [ ] **Step 4: Implement `getTabs` on `ListUkomApplications`**

Replace `app/Filament/Resources/UkomApplicationResource/Pages/ListUkomApplications.php` with:

```php
<?php

namespace App\Filament\Resources\UkomApplicationResource\Pages;

use App\Filament\Resources\UkomApplicationResource;
use App\Services\UkomApplicationAccess;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListUkomApplications extends ListRecords
{
    protected static string $resource = UkomApplicationResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('labels.page.ukom_verification.nav');
    }

    public function getTabs(): array
    {
        $access = app(UkomApplicationAccess::class);
        $user = Auth::user();

        return [
            'all' => Tab::make('All'),

            'new' => Tab::make('New')
                ->badge(function () use ($access, $user): int {
                    if ($user === null) {
                        return 0;
                    }

                    return $access->applyNewTabFilter(
                        UkomApplicationResource::getEloquentQuery(),
                        $user,
                    )->count();
                })
                ->modifyQueryUsing(function (Builder $query) use ($access, $user): Builder {
                    if ($user === null) {
                        return $query->whereRaw('1 = 0');
                    }

                    return $access->applyNewTabFilter($query, $user);
                }),

            'processed' => Tab::make('Processed')
                ->modifyQueryUsing(function (Builder $query) use ($access, $user): Builder {
                    if ($user === null) {
                        return $query->whereRaw('1 = 0');
                    }

                    return $access->applyProcessedTabFilter($query, $user);
                }),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'new';
    }
}
```

Labels match Verifikasi Kegiatan (`All` / `New` / `Processed`).

- [ ] **Step 5: Run tab + full ukom feature tests**

Run:

```bash
php artisan test tests/Feature/Ukom/PengajuanUkomFlowTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Services/UkomApplicationAccess.php app/Filament/Resources/UkomApplicationResource/Pages/ListUkomApplications.php tests/Feature/Ukom/PengajuanUkomFlowTest.php
git commit -m "feat(ukom): add all/new/processed tabs on verification list"
```

---

### Task 3: Smoke check SuperAdmin tabs (optional but recommended)

**Files:**
- Modify: `tests/Feature/Ukom/PengajuanUkomFlowTest.php`

**Interfaces:**
- Consumes: Task 2 tab helpers
- Produces: regression coverage for SuperAdmin New = open statuses

- [ ] **Step 1: Write SuperAdmin tab test**

```php
public function test_super_admin_new_tab_includes_pending_instansi_and_pending_admin(): void
{
    $calon = $this->makeCalonUser();
    $base = [
        'user_id' => $calon->id,
        'target_c_role_id' => $this->ah->id,
        'agency_type' => RegDepartment::class,
        'agency_id' => $this->department->id,
    ];

    $pendingInstansi = UkomApplication::factory()->pendingInstansi()->create($base);
    $pendingAdmin = UkomApplication::factory()->pendingAdmin()->create($base);
    $accepted = UkomApplication::factory()->acceptedByPembina()->create($base);

    $super = User::factory()->create();
    $super->assignRole(SystemRole::SuperAdmin->value);
    $this->actingAs($super);

    Livewire::test(ListUkomApplications::class)
        ->assertSet('activeTab', 'new')
        ->assertCanSeeTableRecords([$pendingInstansi, $pendingAdmin])
        ->assertCanNotSeeTableRecords([$accepted])
        ->set('activeTab', 'processed')
        ->assertCanSeeTableRecords([$accepted])
        ->assertCanNotSeeTableRecords([$pendingInstansi, $pendingAdmin]);
}
```

- [ ] **Step 2: Run and confirm pass**

```bash
php artisan test --filter=test_super_admin_new_tab_includes_pending_instansi_and_pending_admin
```

Expected: PASS (no further code if Task 2 helpers are correct).

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Ukom/PengajuanUkomFlowTest.php
git commit -m "test(ukom): cover superadmin verification list tabs"
```

---

## Spec coverage (self-review)

| Spec requirement | Task |
| --- | --- |
| Pembina hide `pending_instansi` | Task 1 |
| Pembina hide instansi-only reject | Task 1 |
| Pembina see `pending_admin` + `admin_reviewed_at` | Task 1 |
| SuperAdmin see everything non-draft | Task 1 + 3 |
| Dual-role = pembina visibility | Task 1 |
| Admin-instansi scope unchanged | Task 1 (no change) + Task 2 New tab |
| Tabs All / New / Processed, default New | Task 2 |
| New badge | Task 2 |
| Role-specific New/Processed filters | Task 2 + 3 |
| Fix tests that used pembina + `pending_instansi` | Task 1 Step 5 |
| No workspace rebuild / no status machine change | N/A (out of scope) |

No placeholders left. Filter helper names are consistent across Task 2 steps and List page.
