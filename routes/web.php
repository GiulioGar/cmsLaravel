<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\FieldControlController;
use App\Http\Controllers\PrimisController;
use App\Http\Controllers\TargetFieldController;
use App\Http\Controllers\FieldQualityController;
use App\Http\Controllers\CampionamentoController;
use App\Http\Controllers\AbilitaUidController;
use App\Http\Controllers\AutotestController;
use App\Http\Controllers\ConceptToolController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\UserProfileController;


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

// Rotta per la pagina 'Campionamento'
Route::get('/campionamento', function () {
    return view('campionamento');
})->name('campionamento');

Route::get('/campionamento', [CampionamentoController::class, 'index'])
     ->name('campionamento');

Route::get('/campionamento/panel-data/{sur_id}', [CampionamentoController::class, 'panelData'])
     ->name('campionamento.panel-data');

Route::post('/campionamento/utenti-disponibili', [CampionamentoController::class, 'utentiDisponibili'])
     ->name('campionamento.utenti');

Route::post('/campionamento/crea', [CampionamentoController::class, 'creaCampioni'])
    ->name('campionamento.crea');

// rotte abilita UID

Route::get('/abilita-uid', [AbilitaUidController::class, 'index'])->name('abilita.uid');
Route::post('/abilita-uid/genera', [AbilitaUidController::class, 'store'])->name('abilita.uid.genera');

Route::post('/panel/store', [AbilitaUidController::class, 'storePanel']);
Route::post('/panel/update', [AbilitaUidController::class, 'updatePanel']);
Route::delete('/panel/delete/{id}', [AbilitaUidController::class, 'deletePanel']);

Route::post('/abilita-uid/show-data', [AbilitaUidController::class, 'showRightPanelData']);
Route::post('/abilita-uid/enable-uids', [AbilitaUidController::class, 'enableUids']);
Route::post('/abilita-uid/reset-iids', [AbilitaUidController::class, 'resetIids']);

//rotte autotest
Route::get('/autotest', [AutotestController::class, 'index'])->name('autotest.index');
Route::post('/autotest/start', [AutotestController::class, 'start'])->name('autotest.start');
Route::post('/autotest/progress', [AutotestController::class, 'progress'])->name('autotest.progress');
Route::post('/autotest/status', [AutotestController::class, 'status'])->name('autotest.status');

// Rotte per il Concept Tool
Route::get('/concept-tool', [ConceptToolController::class, 'index'])->name('concept.index');
Route::post('/concept-tool', [ConceptToolController::class, 'process'])->name('concept.process');

// ============================================
// PANEL - Gestione Utenti
// ============================================

Route::get('/panel/users', [PanelController::class, 'index'])->name('panel.users');
Route::get('/panel/users/data', [PanelController::class, 'getData'])->name('panel.users.data');
Route::get('/panel/info-annuale/{anno}', [PanelController::class, 'getAnnualPanelInfo'])
    ->name('panel.info.annuale');
Route::post('/panel/users/export', [PanelController::class, 'exportUsers'])
    ->name('panel.users.export');
Route::get('/panel/user/{uid}', [UserProfileController::class, 'show'])->name('panel.user.show');
Route::get('/panel/update-activity', [\App\Http\Controllers\PanelController::class, 'updateUserActivity'])
    ->name('panel.users.update.activity');
Route::get('/panel/users/update-actions', [PanelController::class, 'updateUserActions'])
    ->name('panel.users.update.actions');
Route::get('/panel/users/inactive-3y', [PanelController::class, 'getInactiveUsersOver3Years'])
    ->name('panel.users.inactive.3y');
Route::get('/panel/users/inactive-3y/list', [PanelController::class, 'listInactiveUsersOver3Years'])
    ->name('panel.users.inactive.3y.list');



// Rotte gestione stato utente
Route::post('/user/{user_id}/deactivate', [App\Http\Controllers\UserProfileController::class, 'deactivate'])->name('user.deactivate');
Route::post('/user/{user_id}/delete', [App\Http\Controllers\UserProfileController::class, 'delete'])->name('user.delete');
Route::post('/user/{user_id}/activate', [App\Http\Controllers\UserProfileController::class, 'activate'])->name('user.activate');
Route::post('/user/{user_id}/update-info', [App\Http\Controllers\UserProfileController::class, 'updateAnagrafica'])->name('user.update.info');
Route::post('/user/{user_id}/bonus-malus', [App\Http\Controllers\UserProfileController::class, 'assignBonusMalus'])->name('user.bonus.malus');



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









