<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\FieldControlController;
use App\Http\Controllers\PrimisController;
use App\Http\Controllers\TargetFieldController;
use App\Http\Controllers\FieldQualityController;


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

Route::get('/fieldControl', [FieldControlController::class, 'index']);
//csv download
Route::get('/download-csv', [FieldControlController::class, 'downloadCSV'])->name('download.csv');
//chiudi ricerca
Route::post('/close-survey', [FieldControlController::class, 'closeSurvey'])->name('close.survey');
//reset bloccate
Route::post('/reset-bloccate', [FieldControlController::class, 'resetBloccate'])->name('reset.bloccate');

Route::get('/fieldQuality', [FieldQualityController::class, 'index'])
    ->name('fieldQuality.index');


// Pagina 'Imposta Target': mostra la lista domande
Route::get('/fieldControl/targetField', [TargetFieldController::class, 'index'])
     ->name('targetField.index');

// Rotta AJAX per ottenere il dettaglio di una singola domanda
Route::get('/fieldControl/targetField/getQuestionDetail', [TargetFieldController::class, 'getQuestionDetail'])
     ->name('targetField.getQuestionDetail');

     Route::get('/fieldControl/targetField/getTargetUIDs', [TargetFieldController::class, 'getTargetUIDs'])
     ->name('targetField.getTargetUIDs');

Route::get('/fieldControl/targetField/fetchTargets', [TargetFieldController::class, 'fetchTargets'])
     ->name('targetField.fetchTargets');

Route::post('/fieldControl/targetField/addTarget', [TargetFieldController::class, 'addTarget'])
     ->name('targetField.addTarget');

     Route::post('/fieldControl/targetField/assignTarget', [TargetFieldController::class, 'assignTarget'])
     ->name('targetField.assignTarget');

//ROTTE AGGIUNTA PAROLE IN FILE JSON
     Route::post('/fieldQuality/addToWhiteList', [FieldQualityController::class, 'addToWhiteList'])
     ->name('fieldQuality.addToWhiteList');

Route::post('/fieldQuality/addToBlackList', [FieldQualityController::class, 'addToBlackList'])
     ->name('fieldQuality.addToBlackList');



// Rotta per leggere API
Route::get('/primis/info',     [PrimisController::class, 'getInfo']);
Route::get('/primis/projects', [PrimisController::class, 'getProjects']);
Route::get( '/primis/projects/{projectName}/surveys/{surveyId}', [PrimisController::class, 'getSurvey'] );
Route::get('/primis/projects/{projectName}/surveys/{surveyId}/questions', [PrimisController::class, 'getQuestions'] );









