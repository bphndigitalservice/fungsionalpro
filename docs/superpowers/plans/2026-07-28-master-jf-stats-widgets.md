# Master JF Stats Widgets & Filters Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add filter-aware Master JF stats overview widgets (total, by status, by status_kepegawaian, by gol_ruang) plus Client-style AboveContent filters and a collapse toggle on the Master JF list page.

**Architecture:** Four `StatsOverviewWidget`s on `ListMasterJfs` use Filament’s `InteractsWithPageTable` to read `getPageTableQuery()`. Table filters (`AboveContent`) and search drive both the table rows and the widgets. A small shared trait provides `getTablePage()` → `ListMasterJfs::class`.

**Tech Stack:** Laravel 12, Filament v3.2 (`StatsOverviewWidget`, `SelectFilter`, `InteractsWithPageTable`), Livewire 3, PHPUnit 11, Spatie Permission (roles for auth in tests)

## Global Constraints

- Spec: `docs/superpowers/specs/2026-07-28-master-jf-stats-widgets-design.md`
- Widgets registered **only** on Master JF list (`protected static bool $isDiscovered = false`); do **not** add to Dashboard
- Do **not** change Client widgets / ClientResource filters
- No new DB migration (`status_kepegawaian` already exists)
- Multi-select filters: `instansi`, `unit_kerja`, `jabatan` only; single-select: `status`, `status_kepegawaian`, `pengangkatan`, `type`, `gol_ruang`
- Prefer `getStats()` + `Stat::make` (not deprecated `getCards()` / `Card`)
- Indonesian UI copy consistent with Client list (“Sembunyikan Ringkasan” / “Tampilkan Ringkasan”)
- Conventional commit messages; commit after each task

---

## File structure

| File | Responsibility |
| --- | --- |
| `app/Models/MasterJf.php` | `$fillable` incl. `status_kepegawaian`; `HasFactory`; static option helpers |
| `database/factories/MasterJfFactory.php` | Test fixtures for Master JF rows |
| `app/Filament/Resources/MasterJfResource.php` | `status_kepegawaian` column; AboveContent filters |
| `app/Filament/Resources/MasterJfResource/Pages/ListMasterJfs.php` | Header widgets, collapse toggle, widget columns |
| `app/Filament/Widgets/Concerns/InteractsWithMasterJfPageTable.php` | Shared `InteractsWithPageTable` + `getTablePage()` |
| `app/Filament/Widgets/MasterJfNumbersOverview.php` | Filtered total count |
| `app/Filament/Widgets/MasterJfNumbersByStatusOverview.php` | Counts by status |
| `app/Filament/Widgets/MasterJfNumbersByStatusKepegawaianOverview.php` | Counts by status_kepegawaian |
| `app/Filament/Widgets/MasterJfNumbersByGolRuangOverview.php` | Top 6 gol_ruang |
| `phpunit.xml` | SQLite in-memory for tests |
| `tests/Feature/Filament/MasterJfListStatsTest.php` | Collapse + filter-synced stats coverage |

---

### Task 1: MasterJf model helpers + factory

**Files:**
- Modify: `app/Models/MasterJf.php`
- Create: `database/factories/MasterJfFactory.php`
- Modify: `phpunit.xml` (sqlite testing env)
- Test: `tests/Feature/MasterJfModelTest.php`

**Interfaces:**
- Consumes: existing `master_jf` schema (`status_kepegawaian` column already migrated)
- Produces:
  - `MasterJf::statusOptions(): array<string, string>`
  - `MasterJf::pengangkatanOptions(): array<string, string>`
  - `MasterJf::statusKepegawaianOptions(): array<string, string>`
  - `MasterJf::distinctOptions(string $column): array<string, string>`
  - `MasterJfFactory` definition with all fillable fields
  - Model uses `HasFactory`; `$fillable` includes `status_kepegawaian`

- [ ] **Step 1: Configure PHPUnit for sqlite in-memory**

In `phpunit.xml` `<php>` block, ensure these env vars exist (add or replace `DB_DATABASE`):

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

Keep existing `APP_ENV=testing` and other env entries.

- [ ] **Step 2: Write the failing model/factory test**

Create `tests/Feature/MasterJfModelTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\MasterJf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterJfModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_status_kepegawaian_via_factory(): void
    {
        $row = MasterJf::factory()->create([
            'status_kepegawaian' => 'PNS',
            'status' => 'Aktif',
        ]);

        $this->assertDatabaseHas('master_jf', [
            'id' => $row->id,
            'status_kepegawaian' => 'PNS',
            'status' => 'Aktif',
        ]);
    }

    public function test_status_options_match_known_keys(): void
    {
        $options = MasterJf::statusOptions();

        $this->assertArrayHasKey('Aktif', $options);
        $this->assertArrayHasKey('CTLN', $options);
    }
}
```

- [ ] **Step 3: Run test to verify it fails**

Run: `php artisan test --filter=MasterJfModelTest`

Expected: FAIL (missing factory and/or `statusOptions`, and/or `status_kepegawaian` not fillable)

- [ ] **Step 4: Implement model + factory**

Replace `app/Models/MasterJf.php` with:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterJf extends Model
{
    use HasFactory;

    protected $table = 'master_jf';

    protected $fillable = [
        'nama',
        'nip',
        'gol_ruang',
        'jabatan',
        'unit_kerja',
        'instansi',
        'pengangkatan',
        'status',
        'type',
        'status_kepegawaian',
    ];

    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return [
            'Aktif' => 'Aktif',
            'Mengundurkan diri' => 'Mengundurkan diri',
            'Diberhentikan Sementara sebagai PNS' => 'Diberhentikan Sementara sebagai PNS',
            'CTLN' => 'CTLN',
            'Tugas belajar > 6 Bulan' => 'Tugas belajar > 6 Bulan',
            'Ditugaskan secara penuh di luar jabatan' => 'Ditugaskan secara penuh di luar jabatan',
            'Tidak Memenuhi Persyaratan Jabatan' => 'Tidak Memenuhi Persyaratan Jabatan',
        ];
    }

    /** @return array<string, string> */
    public static function pengangkatanOptions(): array
    {
        return [
            'CPNS/PPPK' => 'CPNS/PPPK',
            'Inpassing' => 'Inpassing',
            'PDJL' => 'PDJL',
            'Penyetaraan' => 'Penyetaraan',
        ];
    }

    /** @return array<string, string> */
    public static function statusKepegawaianOptions(): array
    {
        return [
            'PNS' => 'PNS',
            'PPPK' => 'PPPK',
        ];
    }

    /** @return array<string, string> */
    public static function distinctOptions(string $column): array
    {
        return static::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column, $column)
            ->all();
    }
}
```

Create `database/factories/MasterJfFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\MasterJf;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MasterJf> */
class MasterJfFactory extends Factory
{
    protected $model = MasterJf::class;

    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'nip' => fake()->unique()->numerify('##################'),
            'gol_ruang' => fake()->randomElement(['III/a', 'III/b', 'IV/a', null]),
            'jabatan' => fake()->jobTitle(),
            'unit_kerja' => fake()->company(),
            'instansi' => fake()->company(),
            'pengangkatan' => fake()->randomElement(array_keys(MasterJf::pengangkatanOptions())),
            'status' => fake()->randomElement(array_keys(MasterJf::statusOptions())),
            'type' => fake()->randomElement(['central', 'local_province', null]),
            'status_kepegawaian' => fake()->randomElement(['PNS', 'PPPK', null]),
        ];
    }
}
```

Update `MasterJfResource` form selects to use `MasterJf::statusOptions()`, `MasterJf::pengangkatanOptions()`, and `MasterJf::statusKepegawaianOptions()` so form and filters stay aligned (same keys/labels as above).

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter=MasterJfModelTest`

Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Models/MasterJf.php database/factories/MasterJfFactory.php phpunit.xml tests/Feature/MasterJfModelTest.php app/Filament/Resources/MasterJfResource.php
git commit -m "$(cat <<'EOF'
feat(master-jf): add status_kepegawaian fillable, options helpers, factory

EOF
)"
```

---

### Task 2: Table column + AboveContent filters

**Files:**
- Modify: `app/Filament/Resources/MasterJfResource.php`
- Test: `tests/Feature/Filament/MasterJfListFiltersTest.php`

**Interfaces:**
- Consumes: `MasterJf::statusOptions()`, `pengangkatanOptions()`, `statusKepegawaianOptions()`, `distinctOptions($column)`
- Produces: searchable `status_kepegawaian` column; eight filters with `FiltersLayout::AboveContent`

- [ ] **Step 1: Write the failing filter test**

Create `tests/Feature/Filament/MasterJfListFiltersTest.php`:

```php
<?php

namespace Tests\Feature\Filament;

use App\Enums\SystemRole;
use App\Filament\Resources\MasterJfResource\Pages\ListMasterJfs;
use App\Models\MasterJf;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MasterJfListFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsAdmin(): User
    {
        Role::findOrCreate(SystemRole::Admin->value, 'web');

        $user = User::factory()->create();
        $user->assignRole(SystemRole::Admin->value);

        $this->actingAs($user);

        return $user;
    }

    public function test_status_filter_limits_visible_table_records(): void
    {
        $this->actingAsAdmin();

        $aktif = MasterJf::factory()->create(['status' => 'Aktif', 'nama' => 'Aktif Person']);
        $ctln = MasterJf::factory()->create(['status' => 'CTLN', 'nama' => 'CTLN Person']);

        Livewire::test(ListMasterJfs::class)
            ->assertCanSeeTableRecords([$aktif, $ctln])
            ->filterTable('status', 'Aktif')
            ->assertCanSeeTableRecords([$aktif])
            ->assertCanNotSeeTableRecords([$ctln]);
    }

    public function test_instansi_multi_filter_limits_records(): void
    {
        $this->actingAsAdmin();

        $a = MasterJf::factory()->create(['instansi' => 'BPHN', 'nama' => 'BPHN Person']);
        $b = MasterJf::factory()->create(['instansi' => 'Kemenkumham', 'nama' => 'Kemenkumham Person']);

        Livewire::test(ListMasterJfs::class)
            ->filterTable('instansi', ['BPHN'])
            ->assertCanSeeTableRecords([$a])
            ->assertCanNotSeeTableRecords([$b]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=MasterJfListFiltersTest`

Expected: FAIL (filters not defined / filter name missing)

- [ ] **Step 3: Add column + filters to `MasterJfResource::table()`**

In `app/Filament/Resources/MasterJfResource.php`:

1. Add import: `use Illuminate\Database\Eloquent\Builder;` (only if needed; SelectFilter usually does not need a custom query).
2. After the `status` column (or near `status_kepegawaian` logically), add:

```php
Tables\Columns\TextColumn::make('status_kepegawaian')
    ->label('Status Kepegawaian')
    ->searchable(),
```

3. Before `->headerActions([...]`, add filters (insert `->filters([...], layout: ...)` on the table chain):

```php
->filters([
    Tables\Filters\SelectFilter::make('status')
        ->options(fn (): array => MasterJf::statusOptions()),
    Tables\Filters\SelectFilter::make('status_kepegawaian')
        ->label('Status Kepegawaian')
        ->options(fn (): array => MasterJf::statusKepegawaianOptions()),
    Tables\Filters\SelectFilter::make('pengangkatan')
        ->options(fn (): array => MasterJf::pengangkatanOptions()),
    Tables\Filters\SelectFilter::make('type')
        ->label('Tipe')
        ->options(fn (): array => MasterJf::distinctOptions('type'))
        ->searchable(),
    Tables\Filters\SelectFilter::make('gol_ruang')
        ->label('Golongan/Ruang')
        ->options(fn (): array => MasterJf::distinctOptions('gol_ruang'))
        ->searchable(),
    Tables\Filters\SelectFilter::make('instansi')
        ->options(fn (): array => MasterJf::distinctOptions('instansi'))
        ->searchable()
        ->multiple(),
    Tables\Filters\SelectFilter::make('unit_kerja')
        ->label('Unit Kerja')
        ->options(fn (): array => MasterJf::distinctOptions('unit_kerja'))
        ->searchable()
        ->multiple(),
    Tables\Filters\SelectFilter::make('jabatan')
        ->options(fn (): array => MasterJf::distinctOptions('jabatan'))
        ->searchable()
        ->multiple(),
], layout: Tables\Enums\FiltersLayout::AboveContent)
```

Also update form `Select::make('status')`, `pengangkatan`, and `status_kepegawaian` `->options(...)` to call the model helpers if not already done in Task 1.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=MasterJfListFiltersTest`

Expected: PASS

If multi-filter assertion fails because Filament passes a different shape, adjust the test’s `filterTable('instansi', ...)` argument to match Filament v3 multi SelectFilter (array of values) while keeping the production filter `->multiple()`.

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Resources/MasterJfResource.php tests/Feature/Filament/MasterJfListFiltersTest.php
git commit -m "$(cat <<'EOF'
feat(master-jf): add list filters and status_kepegawaian column

EOF
)"
```

---

### Task 3: Shared trait, total widget, list page collapse toggle

**Files:**
- Create: `app/Filament/Widgets/Concerns/InteractsWithMasterJfPageTable.php`
- Create: `app/Filament/Widgets/MasterJfNumbersOverview.php`
- Modify: `app/Filament/Resources/MasterJfResource/Pages/ListMasterJfs.php`
- Test: `tests/Feature/Filament/MasterJfListStatsTest.php`

**Interfaces:**
- Consumes: `ListMasterJfs` as table page; filtered query via `getPageTableQuery()`
- Produces:
  - Trait method `getTablePage(): string` → `ListMasterJfs::class`
  - `MasterJfNumbersOverview` with heading `Jumlah Master JF`, one stat labeled `Total Master JF`
  - `ListMasterJfs::$widgetsCollapsed`, toggle header action, `getHeaderWidgets()`, `getHeaderWidgetsColumns(): 3`

- [ ] **Step 1: Write the failing list/stats test (collapse + total)**

Create `tests/Feature/Filament/MasterJfListStatsTest.php`:

```php
<?php

namespace Tests\Feature\Filament;

use App\Enums\SystemRole;
use App\Filament\Resources\MasterJfResource\Pages\ListMasterJfs;
use App\Filament\Widgets\MasterJfNumbersOverview;
use App\Models\MasterJf;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MasterJfListStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsAdmin(): User
    {
        Role::findOrCreate(SystemRole::Admin->value, 'web');

        $user = User::factory()->create();
        $user->assignRole(SystemRole::Admin->value);
        $this->actingAs($user);

        return $user;
    }

    public function test_list_shows_total_widget_and_collapse_toggle(): void
    {
        $this->actingAsAdmin();

        MasterJf::factory()->count(3)->create();

        Livewire::test(ListMasterJfs::class)
            ->assertSee('Sembunyikan Ringkasan')
            ->assertSeeLivewire(MasterJfNumbersOverview::class)
            ->assertSee('Total Master JF')
            ->assertSee('3');
    }

    public function test_collapse_toggle_hides_and_shows_widgets(): void
    {
        $this->actingAsAdmin();

        Livewire::test(ListMasterJfs::class)
            ->assertSet('widgetsCollapsed', false)
            ->callAction('toggle-widgets')
            ->assertSet('widgetsCollapsed', true)
            ->assertDontSeeLivewire(MasterJfNumbersOverview::class)
            ->callAction('toggle-widgets')
            ->assertSet('widgetsCollapsed', false)
            ->assertSeeLivewire(MasterJfNumbersOverview::class);
    }

    public function test_total_widget_follows_status_filter(): void
    {
        $this->actingAsAdmin();

        MasterJf::factory()->count(2)->create(['status' => 'Aktif']);
        MasterJf::factory()->count(3)->create(['status' => 'CTLN']);

        Livewire::test(MasterJfNumbersOverview::class, [
            'tableFilters' => [
                'status' => ['value' => 'Aktif'],
            ],
        ])
            ->assertSee('Total Master JF')
            ->assertSee('2');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=MasterJfListStatsTest`

Expected: FAIL (widget/page wiring missing)

- [ ] **Step 3: Implement trait + total widget + list page**

Create `app/Filament/Widgets/Concerns/InteractsWithMasterJfPageTable.php`:

```php
<?php

namespace App\Filament\Widgets\Concerns;

use App\Filament\Resources\MasterJfResource\Pages\ListMasterJfs;
use Filament\Widgets\Concerns\InteractsWithPageTable;

trait InteractsWithMasterJfPageTable
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListMasterJfs::class;
    }
}
```

Create `app/Filament/Widgets/MasterJfNumbersOverview.php`:

```php
<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithMasterJfPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MasterJfNumbersOverview extends StatsOverviewWidget
{
    use InteractsWithMasterJfPageTable;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Jumlah Master JF';

    protected function getStats(): array
    {
        $count = $this->getPageTableQuery()->toBase()->getCountForPagination();

        return [
            Stat::make('Total Master JF', number_format($count))
                ->icon('heroicon-o-users'),
        ];
    }
}
```

Replace `app/Filament/Resources/MasterJfResource/Pages/ListMasterJfs.php` with:

```php
<?php

namespace App\Filament\Resources\MasterJfResource\Pages;

use App\Filament\Resources\MasterJfResource;
use App\Filament\Widgets\MasterJfNumbersOverview;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMasterJfs extends ListRecords
{
    protected static string $resource = MasterJfResource::class;

    public bool $widgetsCollapsed = false;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggle-widgets')
                ->label(fn (): string => $this->widgetsCollapsed ? 'Tampilkan Ringkasan' : 'Sembunyikan Ringkasan')
                ->icon(fn (): string => $this->widgetsCollapsed ? 'heroicon-o-chevron-down' : 'heroicon-o-chevron-up')
                ->color('secondary')
                ->action(fn () => $this->widgetsCollapsed = ! $this->widgetsCollapsed),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        if ($this->widgetsCollapsed) {
            return [];
        }

        return [
            MasterJfNumbersOverview::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }
}
```

Note: later tasks append the other three widget classes to `getHeaderWidgets()`.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=MasterJfListStatsTest`

Expected: PASS

If `getCountForPagination()` is awkward on the builder, use `->count()` instead in the widget. If `callAction('toggle-widgets')` fails, use the Filament action name that matches the page header action API for this version (`callAction` vs `mountAction`); keep behavior identical to `ListClients`.

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Widgets/Concerns/InteractsWithMasterJfPageTable.php app/Filament/Widgets/MasterJfNumbersOverview.php app/Filament/Resources/MasterJfResource/Pages/ListMasterJfs.php tests/Feature/Filament/MasterJfListStatsTest.php
git commit -m "$(cat <<'EOF'
feat(master-jf): add filter-aware total stats widget and collapse toggle

EOF
)"
```

---

### Task 4: Breakdown widgets (status, status_kepegawaian, gol_ruang)

**Files:**
- Create: `app/Filament/Widgets/MasterJfNumbersByStatusOverview.php`
- Create: `app/Filament/Widgets/MasterJfNumbersByStatusKepegawaianOverview.php`
- Create: `app/Filament/Widgets/MasterJfNumbersByGolRuangOverview.php`
- Modify: `app/Filament/Resources/MasterJfResource/Pages/ListMasterJfs.php`
- Modify: `tests/Feature/Filament/MasterJfListStatsTest.php`

**Interfaces:**
- Consumes: `InteractsWithMasterJfPageTable`, `MasterJf::statusOptions()`, `MasterJf::statusKepegawaianOptions()`, `getPageTableQuery()`
- Produces: three breakdown widgets registered in `getHeaderWidgets()` after the total widget

- [ ] **Step 1: Extend stats test for breakdowns + gol_ruang filter**

Add to `tests/Feature/Filament/MasterJfListStatsTest.php`:

```php
use App\Filament\Widgets\MasterJfNumbersByGolRuangOverview;
use App\Filament\Widgets\MasterJfNumbersByStatusKepegawaianOverview;
use App\Filament\Widgets\MasterJfNumbersByStatusOverview;

public function test_status_breakdown_reflects_fixture_counts(): void
{
    $this->actingAsAdmin();

    MasterJf::factory()->count(2)->create(['status' => 'Aktif']);
    MasterJf::factory()->count(1)->create(['status' => 'CTLN']);

    Livewire::test(MasterJfNumbersByStatusOverview::class)
        ->assertSee('Aktif')
        ->assertSee('2')
        ->assertSee('CTLN')
        ->assertSee('1');
}

public function test_status_kepegawaian_breakdown_reflects_fixture_counts(): void
{
    $this->actingAsAdmin();

    MasterJf::factory()->count(2)->create(['status_kepegawaian' => 'PNS']);
    MasterJf::factory()->count(4)->create(['status_kepegawaian' => 'PPPK']);

    Livewire::test(MasterJfNumbersByStatusKepegawaianOverview::class)
        ->assertSee('PNS')
        ->assertSee('2')
        ->assertSee('PPPK')
        ->assertSee('4');
}

public function test_gol_ruang_widget_shows_top_counts(): void
{
    $this->actingAsAdmin();

    MasterJf::factory()->count(3)->create(['gol_ruang' => 'III/a']);
    MasterJf::factory()->count(1)->create(['gol_ruang' => 'IV/a']);

    Livewire::test(MasterJfNumbersByGolRuangOverview::class)
        ->assertSee('III/a')
        ->assertSee('3')
        ->assertSee('IV/a')
        ->assertSee('1');
}

public function test_total_widget_follows_gol_ruang_filter(): void
{
    $this->actingAsAdmin();

    MasterJf::factory()->count(2)->create(['gol_ruang' => 'III/a']);
    MasterJf::factory()->count(5)->create(['gol_ruang' => 'IV/a']);

    Livewire::test(MasterJfNumbersOverview::class, [
        'tableFilters' => [
            'gol_ruang' => ['value' => 'III/a'],
        ],
    ])
        ->assertSee('2');
}
```

- [ ] **Step 2: Run new tests to verify they fail**

Run: `php artisan test --filter=MasterJfListStatsTest`

Expected: FAIL on new breakdown tests

- [ ] **Step 3: Implement the three breakdown widgets**

`app/Filament/Widgets/MasterJfNumbersByStatusOverview.php`:

```php
<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithMasterJfPageTable;
use App\Models\MasterJf;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class MasterJfNumbersByStatusOverview extends StatsOverviewWidget
{
    use InteractsWithMasterJfPageTable;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Master JF berdasarkan Status';

    protected function getStats(): array
    {
        $counts = $this->getPageTableQuery()
            ->toBase()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [];

        foreach (MasterJf::statusOptions() as $value => $label) {
            $stats[] = Stat::make($label, number_format((int) ($counts[$value] ?? 0)))
                ->icon($value === 'Aktif' ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle');
        }

        $unknown = 0;
        foreach ($counts as $key => $total) {
            if ($key === null || $key === '') {
                $unknown += (int) $total;
            }
        }

        if ($unknown > 0) {
            $stats[] = Stat::make('Tidak diketahui', number_format($unknown))
                ->icon('heroicon-o-question-mark-circle');
        }

        return $stats;
    }
}
```

`app/Filament/Widgets/MasterJfNumbersByStatusKepegawaianOverview.php`:

```php
<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithMasterJfPageTable;
use App\Models\MasterJf;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class MasterJfNumbersByStatusKepegawaianOverview extends StatsOverviewWidget
{
    use InteractsWithMasterJfPageTable;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Master JF berdasarkan Status Kepegawaian';

    protected function getStats(): array
    {
        $counts = $this->getPageTableQuery()
            ->toBase()
            ->select('status_kepegawaian', DB::raw('COUNT(*) as total'))
            ->groupBy('status_kepegawaian')
            ->pluck('total', 'status_kepegawaian');

        $stats = [];

        foreach (MasterJf::statusKepegawaianOptions() as $value => $label) {
            $stats[] = Stat::make($label, number_format((int) ($counts[$value] ?? 0)))
                ->icon('heroicon-o-identification');
        }

        $unknown = 0;
        foreach ($counts as $key => $total) {
            if ($key === null || $key === '') {
                $unknown += (int) $total;
            }
        }

        if ($unknown > 0) {
            $stats[] = Stat::make('Tidak diketahui', number_format($unknown))
                ->icon('heroicon-o-question-mark-circle');
        }

        return $stats;
    }
}
```

`app/Filament/Widgets/MasterJfNumbersByGolRuangOverview.php`:

```php
<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithMasterJfPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class MasterJfNumbersByGolRuangOverview extends StatsOverviewWidget
{
    use InteractsWithMasterJfPageTable;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Master JF berdasarkan Golongan/Ruang';

    protected function getStats(): array
    {
        $rows = $this->getPageTableQuery()
            ->toBase()
            ->select('gol_ruang', DB::raw('COUNT(*) as total'))
            ->groupBy('gol_ruang')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $stats = [];

        foreach ($rows as $row) {
            $label = $row->gol_ruang ?: 'Tidak diketahui';
            $stats[] = Stat::make($label, number_format((int) $row->total))
                ->icon('heroicon-o-academic-cap');
        }

        return $stats;
    }
}
```

Update `ListMasterJfs::getHeaderWidgets()` return (when not collapsed) to:

```php
return [
    MasterJfNumbersOverview::class,
    MasterJfNumbersByStatusOverview::class,
    MasterJfNumbersByStatusKepegawaianOverview::class,
    MasterJfNumbersByGolRuangOverview::class,
];
```

Add the corresponding `use` imports.

- [ ] **Step 4: Run full Master JF test suite**

Run:

```bash
php artisan test --filter=MasterJf
```

Expected: all Master JF feature tests PASS

- [ ] **Step 5: Manual smoke check (optional but recommended)**

```bash
php artisan serve
```

Open Master JF list as an admin: confirm filters AboveContent, four widget blocks, collapse toggle, and that filtering by status updates the total card.

- [ ] **Step 6: Commit**

```bash
git add app/Filament/Widgets/MasterJfNumbersByStatusOverview.php app/Filament/Widgets/MasterJfNumbersByStatusKepegawaianOverview.php app/Filament/Widgets/MasterJfNumbersByGolRuangOverview.php app/Filament/Resources/MasterJfResource/Pages/ListMasterJfs.php tests/Feature/Filament/MasterJfListStatsTest.php
git commit -m "$(cat <<'EOF'
feat(master-jf): add status, kepegawaian, and gol_ruang stats widgets

EOF
)"
```

---

## Spec coverage checklist

| Spec requirement | Task |
| --- | --- |
| Total + status + status_kepegawaian + gol_ruang widgets | 3, 4 |
| Live with table search/filters via `InteractsWithPageTable` | 3, 4 |
| Collapse toggle like Client | 3 |
| AboveContent filters for 8 fields | 2 |
| Multi vs single select rules | 2 |
| `status_kepegawaian` fillable + column | 1, 2 |
| Not on dashboard (`$isDiscovered = false`) | 3, 4 |
| Tests: collapse, status filter, distinct filter, breakdowns | 2, 3, 4 |
| No Client widget changes / no new migration | honored |

## Plan self-review notes

- Spec coverage mapped in the checklist above; no open gaps.
- Placeholder scan: none.
- Names/types consistent: trait `InteractsWithMasterJfPageTable`, four `MasterJfNumbers*` widgets, filters match the spec field list.
- `ListMasterJfs` grows header widgets across Task 3 → Task 4 deliberately so Task 3 stays independently testable.
