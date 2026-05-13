<?php

namespace App\Models;

use App\Models\Concern\BelongsToClient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ClientIdentity extends Model
{
    use HasFactory;
    use BelongsToClient;


    public function getPhotoUrlAttribute()
    {
        if (!$this->photo) {
            return asset('images/default-profile.png');
        }

        // Check if using S3/External storage
        if (config('filesystems.default') === 's3') {
            return Storage::temporaryUrl($this->photo, now()->addHours(2));
        }

        return Storage::url($this->photo);
    }
}
