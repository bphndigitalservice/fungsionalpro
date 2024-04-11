<?php

namespace Database\Seeders;

use App\Models\RegGrade;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    public array $grades = [
        ['Juru Muda', 'Ia'],
        ['Juru Muda Tk. I', 'Ib'],
        ['Juru', 'Ic'],
        ['Juru Tk. I', 'Id'],
        ['Pengatur Muda', 'IIa'],
        ['Pengatur Muda Tk. I', 'IIb'],
        ['Pengatur', 'IIc'],
        ['Pengatur Tk. I', 'IId'],
        ['Penata Muda', 'IIIa'],
        ['Penata Muda Tk. I', 'IIIb'],
        ['Penata', 'IIIc'],
        ['Penata Tk. I', 'IIId'],
        ['Pembina', 'IVa'],
        ['Pembina Tk. I', 'IVb'],
        ['Pembina Muda', 'IVc'],
        ['Pembina Madya', 'IVd'],
        ['Pembina Utama', 'IVe'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->grades as $grade) {
            RegGrade::create([
                'grade_name' => $grade[0],
                'grade_code' => $grade[1],
            ]);
        }
    }
}
