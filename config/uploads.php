<?php

return [
    'max_file_size_kb' => (int) env('UPLOAD_MAX_FILE_SIZE_KB', 15360),

    'image_max_file_size_kb' => (int) env('UPLOAD_IMAGE_MAX_FILE_SIZE_KB', 15360),

    'allowed_image_extensions' => [
        'jpg',
        'jpeg',
        'png',
        'webp',
        'gif',
        'svg',
    ],

    'allowed_image_mime_types' => [
        'jpg' => ['image/jpeg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'gif' => ['image/gif'],
        'svg' => ['image/svg+xml', 'text/plain', 'text/xml', 'application/xml'],
    ],

    'max_dimensions' => [
        'width' => (int) env('UPLOAD_IMAGE_MAX_WIDTH', 12000),
        'height' => (int) env('UPLOAD_IMAGE_MAX_HEIGHT', 12000),
    ],

    'backup_root' => env('UPLOAD_BACKUP_ROOT', 'backups/uploads-reset'),

    'filesystem_directories' => [
        'public/uploads',
        'storage/app/public/uploads',
    ],

    'banner_setting_keys' => [
        'home_slider_1_images',
        'home_slider_1_links',
        'home_banner_1_images',
        'home_banner_1_links',
        'home_banner_2_images',
        'home_banner_2_links',
        'home_banner_3_images',
        'home_banner_3_links',
        'home_banner_4_images',
        'home_banner_4_links',
    ],

    'php_ini_recommendations' => [
        'upload_max_filesize' => '15M',
        'post_max_size' => '20M',
        'client_max_body_size' => '20M',
    ],
];
