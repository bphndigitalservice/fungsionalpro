<?php

namespace App\Services;

use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Enums\JenisKepegawaian;
use App\Models\CRoleLevel;
use App\Models\MasterJf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MasterJfAggregateService
{
    /** @return array{agregasi: array<string, mixed>} */
    public function aggregate(array $filters): array
    {
        $baseQuery = $this->buildFilteredQuery($filters);
        $totalFiltered = (clone $baseQuery)->count();

        return [
            'agregasi' => $this->computeAggregations($baseQuery, $totalFiltered),
        ];
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

        if (isset($filters['province_id'])) {
            $query->where('province_id', $filters['province_id']);
        }

        if ($provinsi = trim((string) ($filters['provinsi'] ?? ''))) {
            $query->whereNull('province_id')
                ->where('provinsi', 'like', '%'.$provinsi.'%');
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

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query;
    }

    /** @return array<string, mixed> */
    protected function computeAggregations(Builder $baseQuery, int $totalFiltered): array
    {
        $aggregateBase = (clone $baseQuery)->toBase()->reorder();

        return [
            'total_jf' => $totalFiltered,
            'by_status' => $this->groupEnumCounts(
                (clone $aggregateBase)->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status'),
                array_column(ClientStatus::cases(), 'value'),
            ),
            'by_status_kepegawaian' => $this->groupEnumCounts(
                (clone $aggregateBase)->select('status_kepegawaian', DB::raw('COUNT(*) as total'))->groupBy('status_kepegawaian')->pluck('total', 'status_kepegawaian'),
                array_column(JenisKepegawaian::cases(), 'value'),
            ),
            'by_pengangkatan' => $this->groupRawCounts(
                (clone $aggregateBase)->select('pengangkatan', DB::raw('COUNT(*) as total'))->groupBy('pengangkatan')->pluck('total', 'pengangkatan'),
            ),
            'by_kluster' => $this->groupEnumCounts(
                (clone $aggregateBase)->select('type', DB::raw('COUNT(*) as total'))->groupBy('type')->pluck('total', 'type'),
                array_column(ClientCluster::cases(), 'value'),
            ),
            'by_jabatan_fungsional' => $this->groupJabatanFungsional($baseQuery),
        ];
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

    /** @return array<string, int> */
    protected function groupJabatanFungsional(Builder $baseQuery): array
    {
        $rows = (clone $baseQuery)
            ->toBase()
            ->reorder()
            ->leftJoin('c_roles', 'c_roles.id', '=', 'master_jf.c_role_id')
            ->select('c_roles.role_name', DB::raw('COUNT(*) as total'))
            ->groupBy('c_roles.role_name')
            ->get();

        $result = [];
        $unknown = 0;

        foreach ($rows as $row) {
            $name = trim((string) ($row->role_name ?? ''));
            if ($name === '') {
                $unknown += (int) $row->total;
                continue;
            }
            $result[$name] = (int) $row->total;
        }

        if ($unknown > 0) {
            $result['unknown'] = $unknown;
        }

        return $result;
    }
}
