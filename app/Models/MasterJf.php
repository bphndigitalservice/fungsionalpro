<?php

namespace App\Models;

use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Enums\JenisKepegawaian;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterJf extends Model
{
    use HasFactory;

    protected $table = 'master_jf';

    protected $fillable = [
        'nama',
        'nip',
        'gol_ruang',
        'jabatan',
        'c_role_id',
        'unit_kerja',
        'instansi',
        'pengangkatan',
        'status',
        'type',
        'status_kepegawaian',
    ];

    protected $casts = [
        'type' => ClientCluster::class,
        'status' => ClientStatus::class,
        'status_kepegawaian' => JenisKepegawaian::class,
    ];

    public function cRole(): BelongsTo
    {
        return $this->belongsTo(CRole::class);
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
