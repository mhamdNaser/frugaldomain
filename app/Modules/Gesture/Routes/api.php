<?php

use App\Modules\Gesture\Controllers\GestureController;
use Illuminate\Support\Facades\Route;



Route::controller(GestureController::class)->group(function () {
    Route::post('gestures', 'store');
    Route::get('gestures', 'index');
    Route::get('/gestures/count/{character}', 'countByCharacter');
});


