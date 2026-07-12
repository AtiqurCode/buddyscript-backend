<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // A resource returned directly from a route auto-wraps in
        // {"data": ...}, but one embedded in a hand-built response (like
        // {"user": ..., "token": ...} on login) doesn't — so responses
        // were inconsistent depending on how each endpoint happened to
        // return its resource. Turning wrapping off everywhere means
        // every endpoint's response shape is just whatever its own keys
        // say it is, with no implicit envelope to remember.
        JsonResource::withoutWrapping();
    }
}
