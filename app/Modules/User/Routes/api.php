<?php

use App\Modules\User\Controllers\AdminRoleController;
use App\Modules\User\Controllers\AuthController;
use App\Modules\User\Controllers\UserController;
use App\Modules\User\Controllers\PermissionsController;
use Illuminate\Support\Facades\Route;


Route::prefix('admin')->group(function () {

    Route::middleware('auth:sanctum')->get('/me', [AuthController::class, 'me']);

    Route::controller(AuthController::class)->group(function () {
        Route::post('adminregister', 'register')->name('adminregister');
        Route::post('adminLogin', 'login')->name('adminLogin');
    });

    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);


        Route::controller(AdminRoleController::class)->group(function () {
            Route::get('/roles', 'allRoles')->name('all-roles');
            Route::post('/allroles', 'index')->name('roles');
            Route::post('/roles', 'store')->name('store-role');
            Route::put('/roles/{id}', 'update')->name('update-role');
            Route::delete('/roles/{id}',  'destroy')->name('delete-role');
            Route::delete('/roles',  'deleteRoleArray')->name('delete-all-roles');
        });

        Route::controller(PermissionsController::class)->group(function () {
            Route::post('all-permissions', 'index')->name('permissions');
            Route::get('all-permissions', 'allPermissions')->name('all-permissions');
            Route::post('permissions', 'store')->name('store-permission');
            Route::put('permissions/{id}', 'update')->name('update-permission');
            Route::post('update-role-permissions/{role}', 'updateRolePermissions')->name('update-role-permissions');
            Route::delete('permissions/{id}', 'destroy')->name('delete-permission');
            Route::delete('permissions', 'destroyArray')->name('delete-all-permission');
        });

        Route::controller(UserController::class)->group(function () {
            Route::post('all-users', 'index')->name('users');
            Route::get('all-users', 'all')->name('all-users');
            Route::patch('users/{id}/status', 'changStatus')->name('changestatus-user');
            Route::get('user/{id}', 'show')->name('selected-user');
            Route::post('users', 'store')->name('store-user');
            Route::post('users/{id}', 'update')->name('update-user');
            Route::delete('users/{id}', 'destroy')->name('delete-user');
            Route::delete('users', 'destroyArray')->name('delete-all-user');
        });
    });
});
