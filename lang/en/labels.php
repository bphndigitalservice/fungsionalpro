<?php

return [
    'nav' => [
        'client_management' => 'Manajemen Klien',
        'system' => 'Sistem',
        'ukom' => 'Ukom Application',
        'references' => 'Referensi',
        'crole' => 'Jabatan Fungsional',
        'references_province' => 'Provinsi',
        'references_regency' => 'Kota / Kabupaten',
        'references_department' => 'Kementerian & Lembaga',
    ],
    'page' => [
        'client_profile' => [
            'verify_required' => 'Please complete and verify your Identitas first',
        ],
        'dashboard' => [
            'verify_identity_required' => 'Please complete your Identitas and wait for it to be verified',
        ],
        'pengajuan_ukom' => [
            'nav' => 'Ukom Application',
            'title' => 'Ukom Application',
            'verify_required' => 'Complete Identitas and wait for verification before applying for ukom',
            'submit' => 'Kirim',
            'submit_confirm' => 'Apakah Anda yakin akan mengirim pengajuan? Pengajuan hanya dapat dilakukan 1x hingga pengajuan diterima/ditolak',
        ],
        'riwayat_pengajuan_ukom' => [
            'nav' => 'Riwayat Pengajuan',
            'title' => 'Riwayat Pengajuan Ukom',
            'empty' => 'Belum ada pengajuan Ukom.',
        ],
        'ukom_verification' => [
            'nav' => 'Verify Ukom Applications',
            'forward' => 'Teruskan ke Instansi pembina',
            'forward_confirm' => 'Jika pengajuan ini diverifikasi, berkas akan diteruskan ke Instansi Pembina untuk verifikasi akhir.',
            'forwarded' => 'Pengajuan diteruskan ke Instansi Pembina.',
        ],
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
            'active' => 'Aktif',
        ],
    ],
    'form' => [
        'user' => [
            'heading' => [
                'general' => 'Credentials',
                'general_description' => 'Name, email, and password',
                'role' => 'Peran',
                'role_description' => 'Peran',
                'verification' => 'Verifikasi',
                'verification_descritpion' => 'Verifikasi Email',
            ],
            'fields' => [
                'name' => 'Nama',
                'email' => 'Email',
                'Password' => 'Kata Sandi',
                'role' => 'Peran',
                'verification' => 'Verifikasi Email',
            ],
        ],
        'client' => [
            'tab_info' => 'Identitas ASN',
            'tab_file' => 'Dokumen Prasyarat',
            'heading' => [
                'client_identity' => 'Data Pribadi',
                'client_identity_description' => 'Nama, Alamat, Gender',
                'client_education' => 'Pendidikan Terakhir',
                'client_education_description' => 'Pendidikan Terakhir',
                'client_employee_information' => 'Data Kepegawaian',
                'client_employee_information_description' => 'NIP, Jabatan, Jenjang',
            ],
            'fields' => [
                'name' => 'Nama',
                'academic_title' => 'Gelar Akademik',
                'gender' => 'Jenis Kelamin',
                'phone_number' => 'Nomor Telepon',
                'address' => 'Alamat',
                'photo' => 'Pas Foto',
                'education_level' => 'Jenjang Pendidikan',
                'university_name' => 'Universitas / Institusi Pendidikan',
                'program_name' => 'Jurusan',
                'gpa' => 'IPK',
                'certificate' => 'Ijazah',
                'nip' => 'NIP',
                'crole_name' => 'Jabatan',
                'crole_grade' => 'Jenjang',
                'client_cluster' => 'Kluster ASN',
                'status' => 'Status',
                'assignation_type' => 'Jenis Pengangkatan',
                'agency' => 'Instansi',
                'echelon' => 'Unit Kerja',

            ],
        ],
        'crole' => [
            'fields' => [
                'role_name' => 'Jabatan',
                'active' => 'Aktif',
            ],
        ],
    ],
];
