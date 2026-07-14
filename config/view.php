<?php

return [

    'paths' => [
        resource_path('views'),
    ],

    // Don't use realpath() here — it returns false when the directory is
    // missing on a fresh FTP deploy and Blade then dies with
    // "Please provide a valid cache path."
    'compiled' => env(
        'VIEW_COMPILED_PATH',
        storage_path('framework/views'),
    ),

];
