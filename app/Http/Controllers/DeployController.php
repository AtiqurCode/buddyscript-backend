<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeployController extends Controller
{
    /**
     * Secret-gated migrate / cache clear for cPanel when SSH isn't available.
     *
     * GET /deploy?key=DEPLOY_SECRET&run=all
     * Allowed run: migrate | clear | all
     */
    public function __invoke(Request $request, Kernel $kernel): Response
    {
        $secret = (string) config('app.deploy_secret', '');
        $key = (string) $request->query('key', '');

        if ($secret === '' || ! hash_equals($secret, $key)) {
            return response(
                "Forbidden.\nSet DEPLOY_SECRET in .env and pass ?key=...\n",
                403,
            )->header('Content-Type', 'text/plain; charset=utf-8');
        }

        $run = strtolower((string) $request->query('run', 'all'));

        $commands = match ($run) {
            'migrate' => [
                ['migrate', ['--force' => true]],
            ],
            'clear' => [
                ['optimize:clear'],
            ],
            'all' => [
                ['migrate', ['--force' => true]],
                ['optimize:clear'],
            ],
            default => null,
        };

        if ($commands === null) {
            return response(
                "Unknown run={$run}\nAllowed: migrate | clear | all\n",
                400,
            )->header('Content-Type', 'text/plain; charset=utf-8');
        }

        $output = "Running run={$run}\n".str_repeat('-', 40)."\n";

        foreach ($commands as $command) {
            $name = $command[0];
            $params = $command[1] ?? [];

            $output .= "\n> php artisan {$name}\n";
            $exitCode = $kernel->call($name, $params);
            $output .= $kernel->output();
            $output .= "exit={$exitCode}\n";

            if ($exitCode !== 0) {
                $output .= "\nStopped — command failed.\n";

                return response($output, 500)
                    ->header('Content-Type', 'text/plain; charset=utf-8')
                    ->header('X-Robots-Tag', 'noindex, nofollow');
            }
        }

        $output .= "\nDone.\n";

        return response($output, 200)
            ->header('Content-Type', 'text/plain; charset=utf-8')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
