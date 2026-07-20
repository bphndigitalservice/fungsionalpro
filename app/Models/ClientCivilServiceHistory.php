<?php

namespace App\Models;

use App\Models\Concern\BelongsToClient;
use Illuminate\Database\Eloquent\Model;

class ClientCivilServiceHistory extends Model
{
    use BelongsToClient;

    protected $guarded = ['id'];

}
