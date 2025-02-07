<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;

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
