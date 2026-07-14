<?php

use App\Http\Controllers\DeployController;
use Illuminate\Support\Facades\Route;

// Pure API backend — the React app is the only frontend. This just
// confirms the server is up when you hit the domain in a browser.
Route::get('/', fn () => response()->json(['status' => 'ok', 'service' => 'Buddy Script API']));

Route::get('/deploy', DeployController::class)->middleware('throttle:10,1');
