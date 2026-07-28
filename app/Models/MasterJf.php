<?php

namespace App\Models;

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

    public function cRole(): BelongsTo
    {
        return $this->belongsTo(CRole::class);
    }

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
