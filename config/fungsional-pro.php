<?php

return [
    // file upload section
    'max_upload_file_size' => 750, // in KB,
    'max_media_file_size' => 750,
    'accepted_media_type' => ['image/jpeg', 'image/png'],
    'accepted_document_type' => ['application/pdf'],

    //cache section
    'cache' => [
        'point_remember' => 60 * 60, // one hour
    ],

    //S3 Config
    's3' => [
        'visibility' => 'private',
    ],
];
