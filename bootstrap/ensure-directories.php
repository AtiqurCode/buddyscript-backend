<?php

/**
 * Ensure Laravel's writable dirs exist. On cPanel/FTP deploys the
 * storage/framework/* folders often don't land on the server (empty dirs /
 * .gitignore-only paths get skipped), and realpath(storage/.../views)
 * returns false → "Please provide a valid cache path."
 */
$base = dirname(__DIR__);

$directories = [
    $base.'/storage/app/public',
    $base.'/storage/app/private',
    $base.'/storage/framework/cache/data',
    $base.'/storage/framework/sessions',
    $base.'/storage/framework/testing',
    $base.'/storage/framework/views',
    $base.'/storage/logs',
    $base.'/bootstrap/cache',
];

foreach ($directories as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
}
