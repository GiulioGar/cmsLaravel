<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SurveyController;

// Rotte per il login e logout
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

// Rotta protetta per la pagina index
Route::get('/index', function () {
    return view('index');
})->middleware('auth.custom')->name('index');


// Rotta per la pagina index che mostra i progetti in corso
Route::get('/index', [DashboardController::class, 'index'])->name('index');

// Rotte per la gestione delle survey
Route::get('/surveys', [SurveyController::class, 'index'])->name('surveys.index');
Route::get('/surveys/data', [SurveyController::class, 'getData'])->name('surveys.data');
// "Modifica" nella colonna 'campo_edit':
Route::get('/surveys/{id}/edit', [SurveyController::class, 'edit'])->name('surveys.edit');
// Se vogliamo un update con PATCH/PUT
Route::put('/surveys/{id}', [SurveyController::class, 'update'])->name('surveys.update');
Route::post('/surveys/{id}/update', [SurveyController::class, 'update'])->name('surveys.update');
Route::post('/surveys', [SurveyController::class, 'store'])->name('surveys.store');
Route::get('/surveys/available-sids', [SurveyController::class, 'getAvailableSurIds'])
    ->name('surveys.available-sids');
Route::get('/surveys/prj-info', [SurveyController::class, 'getPrjInfo'])
    ->name('surveys.prj-info');
Route::get('/surveys/get-client-by-prj', [SurveyController::class, 'getClientByPrj'])
    ->name('surveys.getClientByPrj');









