<?php

return [
    'nav' => [
        'client_management' => 'Manajemen Klien',
        'system' => 'Sistem',
        'references' => 'Referensi',
        'crole' => 'Jabatan Fungsional',
        'references_grade' => 'Pangkat/Golongan',
        'references_province' => 'Provinsi',
        'references_regency' => 'Kota / Kabupaten',
        'references_department' => 'Kementerian & Lembaga',
    ],
    'table' => [
        'client' => [
            'id' => 'ID',
            'nip' => 'NIP',
            'name' => 'Nama',
            'role' => 'Jabatan',
            'grade' => 'Jenjang',
            'cluster' => 'Kluster',
            'agency' => 'agency',
            'echelon' => 'Unit Kerja',
            'echelon_text' => 'Unit Kerja - Typed',
            'status' => 'Status',
            'assignation_type' => 'Pengangkatan',
        ],
        'crole' => [
            'name' => 'Nama Jabatan Fungsional',
            'active' => 'Aktif'
        ]
    ],
    'form' => [
        'user'=> [
            'heading' => [
                'general' => 'Umum',
                'general_description' => 'Name, email, and Password',
                'role' => 'Peran',
                'role_description' => 'Peran',
                'verification' => 'Verifikasi',
                'verification_descritpion' => 'Verifikasi Email'
            ],
            'fields' => [
                'name' => 'Nama',
                'email' => 'Email',
                'Password' => 'Kata Sandi',
                'role' => 'Peran',
                'verification' => 'Verifikasi Email'
            ]
        ],
        'client' => [
            'tab_info' => 'Identitas ASN',
            'tab_file' => 'Rincian',
            'tab_user' => 'Pengguna Terkait',
            'heading' => [
                'client_identity' => 'Data Pribadi',
                'client_identity_description' => 'Nama, Alamat, Gender',
                'client_education' => 'Pendidikan Terakhir',
                'client_education_description' => 'Pendidikan Terakhir',
                'client_employee_information' => 'Data Kepegawaian',
                'client_employee_information_description' => 'NIP, Jabatan, Jenjang'
            ],
            'fields' => [
                'name' => 'Nama',
                'academic_title' => 'Gelar Akademik',
                'gender' => 'Jenis Kelamin',
                'phone_number' => 'Nomor Telepon',
                'address' => 'Alamat',
                'photo' => 'Pas Foto',
                'university_name' => 'Universitas',
                'program_name' => 'Jurusan',
                'gpa' => 'IPK',
                'certificate' => 'Ijazah/Transkrip',
                'nip' => 'NIP',
                'crole_name' => 'Jabatan',
                'crole_grade' => 'Jenjang',
                'client_cluster' => 'Kluster ASN',
                'status' => 'Status',
                'assignation_type' => 'Jenis Pengangkatan',
                'agency' => 'Instansi',
                'echelon' => 'Unit Kerja',
                'grade' => 'Pangkat/Golongan'

            ]
        ],
        'grade'=>[
            'fields' => [
                'grade_name' => 'Pangkat',
                'grade_code' => 'Golongan/Ruang'
            ]
        ],
        'crole' => [
            'fields' => [
                'role_name' => 'Jabatan',
                'active' => 'Aktif'
            ]
        ]
    ],
];
