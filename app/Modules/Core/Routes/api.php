<?php


use App\Modules\Core\Controllers\ImageController;
use Illuminate\Support\Facades\Route;


Route::post('/convert-image', [ImageController::class, 'convert']);
Route::get('/download-image/{fileName}', [ImageController::class, 'download']);
