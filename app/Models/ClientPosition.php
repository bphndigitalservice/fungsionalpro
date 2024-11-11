<?php

namespace App\Models;

use App\Models\Concern\BelongsToClient;
use Illuminate\Database\Eloquent\Model;

class ClientPosition extends Model
{
    use BelongsToClient;
}
