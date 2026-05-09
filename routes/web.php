<?php

use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'role:partner'])->prefix('partner')->group(function () {
    Route::get('/notifications', function () {
        return view('partner.notifications.index');
    })->name('partner.notifications.index');

    Route::get('/notifications/{id}', function (string $id) {
        return view('partner.notifications.show', ['notificationId' => $id]);
    })->name('partner.notifications.show');
});
