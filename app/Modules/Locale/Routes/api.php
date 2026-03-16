<?php

use App\Modules\Locale\Controllers\CityController;
use App\Modules\Locale\Controllers\CountryController;
use App\Modules\Locale\Controllers\LanguageController;
use App\Modules\Locale\Controllers\LocaleController;
use App\Modules\Locale\Controllers\StateController;
use Illuminate\Support\Facades\Route;


Route::get('/locale/{lang}', [LocaleController::class, 'setlocale']);
Route::get('active-languages', [LanguageController::class, 'active'])->name('active-languages');

Route::get('countries', [CountryController::class, 'index'])->name('countries');
Route::get('states/{id}', [StateController::class, 'index'])->name('states-country-id');
Route::get('cities/{id}', [CityController::class, 'index'])->name('cities-state-id');


Route::prefix('admin')->group(function () {

    Route::controller(CountryController::class)->group(function () {
        Route::post('countries', 'allCountry')->name('countries');
    });

    Route::controller(StateController::class)->group(function () {
        Route::post('all-states-id/{id}', 'allstates')->name('all-states-id');
    });

    Route::controller(CityController::class)->group(function () {
        Route::post('all-cities-id/{id}', 'allcities')->name('all-cities-id');
    });

    Route::controller(LanguageController::class)->group(function () {
        Route::get('all-languages', 'index')->name('all-languages');
        Route::post('add-language', 'store')->name('add-language');
        Route::post('add-word/{slug}', 'addWordToAdminFile')->name('add-word');
        Route::post('show-translation/{slug}', 'show')->name('show-translation');
        Route::get('delete-language/{id}', 'destroy')->name('delete-language');
        Route::post('delete-languages', 'destroyarray')->name('delete-languages');
        Route::get('changestatus-language/{id}', 'changestatus')->name('changestatus-language');
    });
});
