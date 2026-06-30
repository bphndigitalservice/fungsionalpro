<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterJf extends Model
{
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
    ];
}
