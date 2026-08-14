<?php

use App\Http\Controllers\Api\V1\MasterJfController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['verify.api.key', 'throttle:60,1'])
    ->group(function () {
        Route::get('ping', fn () => response()->json(['ok' => true]));
        Route::get('master-jf', [MasterJfController::class, 'index']);
    });
