<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concern\BelongsToClient;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientActivity extends Model
{
    use HasFactory;
    use BelongsToClient;


    protected $casts = [
    'activity_details' => 'array',
    ];

    public function client(){
        return $this->belongsTo(Client::class);
    }

    public function verified(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

}