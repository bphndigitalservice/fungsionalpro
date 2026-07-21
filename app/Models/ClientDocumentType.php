<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientDocumentType extends Model
{
    use HasFactory, HasUlids;
    use SoftDeletes;

    protected $guarded = ['id'];

}
