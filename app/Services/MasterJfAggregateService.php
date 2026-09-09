<?php

namespace App\Services;

use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Enums\JenisKepegawaian;
use App\Models\CRole;
use App\Models\CRoleLevel;
use App\Models\MasterJf;
use App\Support\MasterJfAgencyApiMapper;
use App\Support\MasterJfClusterResolver;
use App\Support\MasterJfDisplay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MasterJfAggregateService
{
    /**
     * @return array{data: list<array{c_role_id: int, c_role_label: string, cluster: string, cluster_label: string, aggregate: array, data: list<array{agency_type: ?string, agency_id: ?int, name: string, client_count: int}>}>}
     */
    public function aggregate(array $filters): array
    {
        $baseQuery = $this->buildFilteredQuery($filters);
        $rolesById = CRole::query()->pluck('role_name', 'id');
        $rolesByName = $rolesById->flip();
        $clusterFilter = isset($filters['type']) ? (string) $filters['type'] : null;

        $rows = (clone $baseQuery)
            ->select([
                'id',
                'c_role_id',
                'type',
                'instansi',
                'unit_kerja',
                'jabatan',
                'status',
                'status_kepegawaian',
                'pengangkatan',
                'agency_type',
                'agency_id',
            ])
            ->with(['cRole:id,role_name'])
            ->get();

        $loadable = $rows->filter(function (MasterJf $row): bool {
            return MasterJfAgencyApiMapper::shortType(
                is_string($row->agency_type) ? $row->agency_type : null,
            ) !== null && $row->agency_id !== null;
        });

        if ($loadable->isNotEmpty()) {
            $loadable->load('agenciable');
        }

        /** @var array<string, array{c_role_id: int, c_role_label: string, cluster: string, cluster_label: string, rows: Collection<int, MasterJf>}> $segments */
        $segments = [];

        foreach ($rows as $row) {
            $clusterId = MasterJfClusterResolver::resolve(
                $row->type,
                $row->instansi,
                $row->unit_kerja,
            )->value;

            if ($clusterFilter !== null && $clusterId !== $clusterFilter) {
                continue;
            }

            if (
                $this->hasDaerahFilter($filters)
                && ! $this->isCentralClusterFilter($filters)
                && $clusterId === ClientCluster::Central->value
            ) {
                continue;
            }

            [$cRoleId, $cRoleLabel] = $this->resolveJfType(
                $row->c_role_id,
                $row->cRole?->role_name ?? MasterJfDisplay::inferRoleNameFromJabatan($row->jabatan),
                $rolesById,
                $rolesByName,
            );

            $key = $cRoleId.':'.$clusterId;

            if (! isset($segments[$key])) {
                $segments[$key] = [
                    'c_role_id' => $cRoleId,
                    'c_role_label' => $cRoleLabel,
                    'cluster' => $clusterId,
                    'cluster_label' => MasterJfAgencyApiMapper::clusterLabel($clusterId),
                    'rows' => collect(),
                ];
            }

            $segments[$key]['rows']->push($row);
        }

        $groups = [];

        foreach ($segments as $segment) {
            $groups[] = [
                'c_role_id' => $segment['c_role_id'],
                'c_role_label' => $segment['c_role_label'],
                'cluster' => $segment['cluster'],
                'cluster_label' => $segment['cluster_label'],
                'aggregate' => $this->computeSliceAggregationsFromCollection($segment['rows']),
                'data' => $this->buildInstansiListFromCollection($segment['rows']),
            ];
        }

        usort($groups, function (array $a, array $b): int {
            return [$a['c_role_id'], $this->clusterSortOrder($a['cluster'])]
                <=> [$b['c_role_id'], $this->clusterSortOrder($b['cluster'])];
        });

        return ['data' => $groups];
    }

    public function buildFilteredQuery(array $filters): Builder
    {
        $query = MasterJf::query();

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(function (Builder $q) use ($search) {
                $like = '%'.$search.'%';
                $q->where('nama', 'like', $like)
                    ->orWhere('nip', 'like', $like)
                    ->orWhere('instansi', 'like', $like)
                    ->orWhere('unit_kerja', 'like', $like);
            });
        }

        if (isset($filters['c_role_id'])) {
            $query->where('c_role_id', $filters['c_role_id']);
        }

        if (isset($filters['c_role_level_id'])) {
            $level = CRoleLevel::query()->find($filters['c_role_level_id']);
            if ($level?->level) {
                $query->whereRaw('LOWER(jabatan) LIKE ?', ['%'.strtolower($level->level).'%']);
            } else {
                $query->whereRaw('0 = 1');
            }
        }

        if ($jenjang = trim((string) ($filters['jenjang'] ?? ''))) {
            $query->whereRaw('LOWER(jabatan) LIKE ?', ['%'.strtolower($jenjang).'%']);
        }

        if (isset($filters['reg_grade_id'])) {
            $query->where('reg_grade_id', $filters['reg_grade_id']);
        }

        if (! $this->isCentralClusterFilter($filters) && $this->hasDaerahFilter($filters)) {
            $provinceId = $filters['province_id'] ?? null;
            $provinsi = trim((string) ($filters['provinsi'] ?? ''));

            $query->where(function (Builder $q) use ($provinceId, $provinsi) {
                if ($provinceId !== null) {
                    $q->where('province_id', $provinceId);
                }

                if ($provinsi !== '') {
                    $method = $provinceId !== null ? 'orWhere' : 'where';

                    $q->{$method}(function (Builder $q2) use ($provinsi) {
                        $q2->whereNull('province_id')
                            ->where('provinsi', 'like', '%'.$provinsi.'%');
                    });
                }
            });
        }

        if ($pengangkatan = trim((string) ($filters['pengangkatan'] ?? ''))) {
            $query->where('pengangkatan', $pengangkatan);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['status_kepegawaian'])) {
            $query->where('status_kepegawaian', $filters['status_kepegawaian']);
        }

        return $query;
    }

    protected function isCentralClusterFilter(array $filters): bool
    {
        return isset($filters['type'])
            && (string) $filters['type'] === ClientCluster::Central->value;
    }

    protected function hasDaerahFilter(array $filters): bool
    {
        return isset($filters['province_id'])
            || trim((string) ($filters['provinsi'] ?? '')) !== '';
    }

    /**
     * @param  Collection<int, string>  $rolesById
     * @param  Collection<string, int>  $rolesByName
     * @return array{0: int, 1: string}
     */
    protected function resolveJfType(mixed $cRoleId, mixed $roleName, Collection $rolesById, Collection $rolesByName): array
    {
        if ($cRoleId !== null && $rolesById->has($cRoleId)) {
            return [(int) $cRoleId, (string) $rolesById[$cRoleId]];
        }

        $label = trim((string) $roleName);
        if ($label !== '' && $rolesByName->has($label)) {
            return [$rolesByName[$label], $label];
        }

        return [0, 'unknown'];
    }

    protected function clusterSortOrder(string $clusterId): int
    {
        return match ($clusterId) {
            ClientCluster::Central->value => 1,
            ClientCluster::LocalProvince->value => 2,
            ClientCluster::LocalRegency->value => 3,
            default => 99,
        };
    }

    /** @param  Collection<int, MasterJf>  $rows */
    protected function computeSliceAggregationsFromCollection(Collection $rows): array
    {
        $statusCounts = [];
        $kepegawaianCounts = [];
        $pengangkatanCounts = [];
        $jenjangCounts = $this->emptyJenjangCounts();

        foreach ($rows as $row) {
            $this->incrementEnumCount($statusCounts, $row->status?->value ?? $row->status);
            $this->incrementEnumCount($kepegawaianCounts, $row->status_kepegawaian?->value ?? $row->status_kepegawaian);
            $this->incrementRawCount($pengangkatanCounts, $row->pengangkatan);
            $this->incrementJenjangCount($jenjangCounts, $row->jabatan);
        }

        return [
            'total_jf' => $rows->count(),
            'by_jenjang' => $jenjangCounts,
            'by_status' => $this->finalizeEnumCounts($statusCounts, array_column(ClientStatus::cases(), 'value')),
            'by_status_kepegawaian' => $this->finalizeEnumCounts($kepegawaianCounts, array_column(JenisKepegawaian::cases(), 'value')),
            'by_pengangkatan' => $this->finalizeRawCounts($pengangkatanCounts),
        ];
    }

    /**
     * @param  Collection<int, MasterJf>  $rows
     * @return list<array{agency_type: ?string, agency_id: ?int, name: string, client_count: int}>
     */
    protected function buildInstansiListFromCollection(Collection $rows): array
    {
        $buckets = [];
        $unknownCount = 0;

        foreach ($rows as $row) {
            $shortType = MasterJfAgencyApiMapper::shortType(
                is_string($row->agency_type) ? $row->agency_type : null,
            );
            $agencyId = $row->agency_id;

            if ($shortType === null || $agencyId === null) {
                $unknownCount++;

                continue;
            }

            $related = $row->agenciable;
            if ($related === null) {
                $unknownCount++;

                continue;
            }

            $key = $shortType.':'.$agencyId;
            if (! isset($buckets[$key])) {
                $buckets[$key] = [
                    'agency_type' => $shortType,
                    'agency_id' => (int) $agencyId,
                    'name' => (string) $related->name,
                    'client_count' => 0,
                ];
            }
            $buckets[$key]['client_count']++;
        }

        $instansi = array_values($buckets);
        usort($instansi, fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

        if ($unknownCount > 0) {
            $instansi[] = [
                'agency_type' => null,
                'agency_id' => null,
                'name' => 'unknown',
                'client_count' => $unknownCount,
            ];
        }

        return $instansi;
    }

    /** @param array<string, int> $counts */
    protected function incrementEnumCount(array &$counts, mixed $value): void
    {
        $key = $value === null ? '' : (string) $value;
        $counts[$key] = ($counts[$key] ?? 0) + 1;
    }

    /** @param array<string, int> $counts */
    protected function incrementRawCount(array &$counts, mixed $value): void
    {
        $key = trim((string) ($value ?? ''));
        $counts[$key] = ($counts[$key] ?? 0) + 1;
    }

    /** @param array<string, int> $counts */
    protected function incrementJenjangCount(array &$counts, ?string $jabatan): void
    {
        $jenjang = MasterJfDisplay::parseJenjangFromJabatan($jabatan) ?? 'unknown';
        $counts[$jenjang] = ($counts[$jenjang] ?? 0) + 1;
    }

    /** @param array<string, int> $counts @param list<string> $knownValues @return array<string, int> */
    protected function finalizeEnumCounts(array $counts, array $knownValues): array
    {
        return $this->groupEnumCounts(collect($counts), $knownValues);
    }

    /** @param array<string, int> $counts @return array<string, int> */
    protected function finalizeRawCounts(array $counts): array
    {
        return $this->groupRawCounts(collect($counts));
    }

    /** @return array<string, int> */
    protected function emptyJenjangCounts(): array
    {
        $counts = array_fill_keys(MasterJfDisplay::JENJANG_LABELS, 0);
        $counts['unknown'] = 0;

        return $counts;
    }

    /** @param Collection<int|string, mixed> $counts @param list<string> $knownValues */
    protected function groupEnumCounts(Collection $counts, array $knownValues): array
    {
        $result = [];
        $unknown = 0;

        foreach ($counts as $key => $total) {
            $key = $key === null ? '' : (string) $key;
            if ($key === '' || ! in_array($key, $knownValues, true)) {
                $unknown += (int) $total;
                continue;
            }
            $result[$key] = (int) $total;
        }

        if ($unknown > 0) {
            $result['unknown'] = $unknown;
        }

        foreach ($knownValues as $value) {
            $result[$value] ??= 0;
        }

        return $result;
    }

    /** @param Collection<int|string, mixed> $counts */
    protected function groupRawCounts(Collection $counts): array
    {
        $result = [];
        $unknown = 0;

        foreach ($counts as $key => $total) {
            $key = $key === null ? '' : trim((string) $key);
            if ($key === '') {
                $unknown += (int) $total;
                continue;
            }
            $result[$key] = (int) $total;
        }

        if ($unknown > 0) {
            $result['unknown'] = $unknown;
        }

        return $result;
    }
}
