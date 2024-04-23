<?php

return [
    'nav' => [
        'client_management' => 'Manajemen Klien',
        'client_menu' => 'My Data',
        'client_profile' => 'Informasi Dasar',
        'client_point' => 'Angka Kredit',
        'client_point_submission_bag' => 'Periode Pelaporan AK',
        'system' => 'Sistem',
        'references' => 'Referensi',
        'crole' => 'Jabatan Fungsional',
        'references_grade' => 'Pangkat/Golongan',
        'references_province' => 'Provinsi',
        'references_regency' => 'Kota / Kabupaten',
        'references_department' => 'Kementerian & Lembaga',

    ],
    'page' => [
        'client_profile' => [
            'nav' => 'Identitas',
            'title' => 'Identitas',
        ],
        'client_point_list' => [
            'nav' => 'Riwayat',
            'title' => 'Riwayat Angka Kredit',
        ],
        'client_point_create' => [
            'nav' => 'Pelaporan AK',
            'title' => 'Pelaporan AK',
        ],
        'client_point_edit' => [
            'nav' => 'Perbaikan Pelaporan AK',
            'title' => 'Perbaikan Pelaporan AK',
        ],
        'v_client_point_submission' => [
            'title' => 'Daftar Pelaporan AK',
        ],
        'v_client_identity_verification' => [
            'title' => 'Verifikasi Indentitas JF',
        ],
        'v_client_point_verification' => [
            'title' => 'Verifikasi Pelaporan AK',
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
            'is_verified' => 'Verified',
            'verified_at' => 'Verified At',
        ],
        'verification' => [
            'identity' => [
                'actions' => [
                    'accept' => 'Terima',
                    'reject' => 'Tolak',
                ],
            ],
            'point' => [
                'modal_heading' => 'Verifikasi Pelaporan AK',
                'actions' => [
                    'accept' => 'Periksa',
                    'reject' => 'Tolak',
                ],
            ],
        ],
        'crole' => [
            'name' => 'Nama Jabatan Fungsional',
            'active' => 'Aktif',
        ],
    ],
    'form' => [
        'user' => [
            'heading' => [
                'general' => 'Umum',
                'general_description' => 'Name, email, and Password',
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
            'tab_file' => 'Rincian',
            'tab_user' => 'Pengguna Terkait',
            'heading' => [
                'client_identity' => 'Data Pribadi',
                'client_identity_description' => 'Nama, Alamat, Gender',
                'client_education' => 'Pendidikan Terakhir',
                'client_education_description' => 'Pendidikan Terakhir',
                'client_employee_information' => 'Data Kepegawaian',
                'client_employee_information_description' => 'NIP, Jabatan, Jenjang',
                'client_detail_cpns_pns' => 'CPNS & PNS',
                'client_detail_cpns_pns_desc' => 'Data terkait CPNS & PNS',
                'client_detail_role' => 'Jabatan',
                'client_detail_role_desc' => 'Data terkait jabatan',
                'client_detail_grade' => 'Pangkat',
                'client_detail_grade_desc' => 'Data terkait kepangkatan',
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
                'grade' => 'Pangkat/Golongan',
                'tmt_cpns' => 'TMT CPNS',
                'tmt_jf_latest' => 'TMT Jabatan Fungsional',
                'latest_jf_no' => 'Nomor SK',
                'file_sk_cpns' => 'File SK CPNS',
                'file_sk_pns' => 'File SK PNS',
                'file_sk_jf_latest' => 'File SK Jabatan Fungsional',
                'tmt_grade_sk_latest' => 'TMT Pangkat Terakhir',
                'grade_sk_latest_no' => 'Nomor SK Pangkat Terakhir',
                'file_sk_grade_latest' => 'File SK Pangkat Terakhir',
                'x_skp2ak_file' => 'Lembar Konversi Predikat Kinerja Ke Angka Kredit',
                'x_accumulated_file' => 'Lembar Akumulasi AK',
                'pak_file' => 'Lembar Penetapan Angka Kredit'
            ],
        ],
        'grade' => [
            'fields' => [
                'grade_name' => 'Pangkat',
                'grade_code' => 'Golongan/Ruang',
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
