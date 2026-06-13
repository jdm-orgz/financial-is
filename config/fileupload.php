<?php

$moduleSizes = json_decode(env('FILE_UPLOAD_MODULE_SIZES', '{}'), true);

return [
    // Global default max file size (in kilobytes)
    'default_max_size' => env('FILE_UPLOAD_MAX_SIZE', 2048),

    'modules' => [
        'app_config' => [
            // Leave as null if you want it to fall back to the global default
            'max_size' => $moduleSizes['app_config'] ?? null,
        ],
    ],
];
