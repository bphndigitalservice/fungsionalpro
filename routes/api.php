<?php

use App\Http\Controllers\Api\V1\CRoleController;
use App\Http\Controllers\Api\V1\CRoleLevelController;
use App\Http\Controllers\Api\V1\MasterJfController;
use App\Http\Controllers\Api\V1\RegProvinceController;
use App\Http\Controllers\Api\V1\RegRegencyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['verify.api.key', 'throttle:60,1'])
    ->group(function () {
        Route::get('ping', fn () => response()->json(['ok' => true]));
        Route::get('master-jf', [MasterJfController::class, 'index']);
        Route::get('c-roles', [CRoleController::class, 'index']);
        Route::get('c-role-levels', [CRoleLevelController::class, 'index']);
        Route::get('reg-provinces', [RegProvinceController::class, 'index']);
        Route::get('reg-regencies', [RegRegencyController::class, 'index']);
    });
