<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {

    Route::middleware(['auth:sanctum', 'role:partner'])->group(function () {


    });
});
