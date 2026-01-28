<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concern\BelongsToClient;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientActivity extends Model
{
    use HasFactory;
    use BelongsToClient;

    public function client(){
        return $this->belongsTo(Client::class);
    }

}