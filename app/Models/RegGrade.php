<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegGrade extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function getCleanNameAttribute(): string
    {
        // Example: Pembina (IV/a) -> IVa
        // Example: Penata Tingkat I - III/d -> IIId
        $name = $this->grade_name;

        if (preg_match('/\((.*?)\)/', $name, $matches)) {
            return str_replace('/', '', $matches[1]);
        }

        if (preg_match('/([IVX]+)\/([a-d])/', $name, $matches)) {
            return $matches[1] . $matches[2];
        }

        return $this->grade_code;
    }
}
