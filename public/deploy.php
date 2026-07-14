<?php

/**
 * cPanel post-deploy runner (no SSH needed).
 *
 * Setup:
 *   1. Add DEPLOY_SECRET to the server .env (long random string)
 *   2. After FTP deploy, open one of the URLs below
 *
 * Examples:
 *   /deploy.php?key=YOUR_SECRET&run=migrate
 *   /deploy.php?key=YOUR_SECRET&run=clear
 *   /deploy.php?key=YOUR_SECRET&run=all
 *   /deploy.php?key=YOUR_SECRET&run=cache
 *   /deploy.php?key=YOUR_SECRET&run=link
 */

use Illuminate\Contracts\Console\Kernel;

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

/** @var Kernel $kernel */
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

$secret = (string) env('DEPLOY_SECRET', '');
$key = (string) ($_GET['key'] ?? '');

if ($secret === '' || ! hash_equals($secret, $key)) {
    http_response_code(403);
    echo "Forbidden.\n";
    echo "Set DEPLOY_SECRET in .env and pass ?key=...\n";
    exit;
}

$run = strtolower((string) ($_GET['run'] ?? 'all'));

$commands = match ($run) {
    'migrate' => [
        ['migrate', ['--force' => true]],
    ],
    'clear' => [
        ['optimize:clear'],
    ],
    'cache' => [
        ['config:cache'],
        ['route:cache'],
    ],
    'link' => [
        ['storage:link', ['--force' => true]],
    ],
    'all' => [
        ['migrate', ['--force' => true]],
        ['optimize:clear'],
    ],
    default => null,
};

if ($commands === null) {
    http_response_code(400);
    echo "Unknown run={$run}\n";
    echo "Allowed: migrate | clear | cache | link | all\n";
    exit;
}

echo "Running run={$run}\n";
echo str_repeat('-', 40)."\n";

foreach ($commands as $command) {
    $name = $command[0];
    $params = $command[1] ?? [];

    echo "\n> php artisan {$name}\n";
    $exitCode = $kernel->call($name, $params);
    echo $kernel->output();
    echo "exit={$exitCode}\n";

    if ($exitCode !== 0) {
        http_response_code(500);
        echo "\nStopped — command failed.\n";
        exit;
    }
}

echo "\nDone.\n";
