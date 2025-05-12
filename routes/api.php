<?php


use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;


Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('refresh', [AuthController::class, 'refresh']);
    Route::get('verify', [AuthController::class, 'verify']);
    Route::post('register', [AuthController::class, 'register']);
    Route::prefix('roles')->group(function () {
        Route::post('assign', [RoleController::class, 'assignRole']);
        Route::post('remove', [RoleController::class, 'removeRole']);
    });
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});
