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
use App\Http\Controllers\PanelUsersController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\PremiPanelController;
use App\Http\Controllers\RecruitmentController;

/*
|--------------------------------------------------------------------------
| ROTTE PUBBLICHE (solo login)
|--------------------------------------------------------------------------
*/
Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

/*
|--------------------------------------------------------------------------
| ROTTE PROTETTE (tutto il gestionale)
|--------------------------------------------------------------------------
| Tutte le route qui sotto richiedono sessione valida (auth.custom).
*/
Route::middleware(['auth.custom'])->group(function () {

    // Home: da / vai alla dashboard
    Route::get('/', function () {
        return redirect()->route('index');
    });

    // Logout (ha senso solo se autenticato)
    Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/index', [DashboardController::class, 'index'])->name('index');

    // ============================================
    // SURVEYS
    // ============================================
    Route::get('/surveys', [SurveyController::class, 'index'])->name('surveys.index');
    Route::get('/surveys/data', [SurveyController::class, 'getData'])->name('surveys.data');
    Route::get('/surveys/{id}/edit', [SurveyController::class, 'edit'])->name('surveys.edit');
    Route::put('/surveys/{id}', [SurveyController::class, 'update'])->name('surveys.update');
    Route::post('/surveys', [SurveyController::class, 'store'])->name('surveys.store');
    Route::get('/surveys/available-sids', [SurveyController::class, 'getAvailableSurIds'])->name('surveys.available-sids');
    Route::get('/surveys/prj-info', [SurveyController::class, 'getPrjInfo'])->name('surveys.prj-info');
    Route::get('/surveys/get-client-by-prj', [SurveyController::class, 'getClientByPrj'])->name('surveys.getClientByPrj');

    // ============================================
    // FIELD CONTROL
    // ============================================
    Route::get('/fieldControl', [FieldControlController::class, 'index']);
    Route::get('/download-csv', [FieldControlController::class, 'downloadCSV'])->name('download.csv');
    Route::post('/close-survey', [FieldControlController::class, 'closeSurvey'])->name('close.survey');
    Route::post('/reset-bloccate', [FieldControlController::class, 'resetBloccate'])->name('reset.bloccate');

    // ============================================
    // FIELD QUALITY
    // ============================================
    Route::get('/fieldQuality', [FieldQualityController::class, 'index'])->name('fieldQuality.index');
    Route::post('/fieldQuality/addToWhiteList', [FieldQualityController::class, 'addToWhiteList'])->name('fieldQuality.addToWhiteList');
    Route::post('/fieldQuality/addToBlackList', [FieldQualityController::class, 'addToBlackList'])->name('fieldQuality.addToBlackList');

    // ============================================
    // CAMPIONAMENTO
    // ============================================
    Route::get('/campionamento', [CampionamentoController::class, 'index'])->name('campionamento');
    Route::get('/campionamento/panel-data/{sur_id}', [CampionamentoController::class, 'panelData'])->name('campionamento.panel-data');
    Route::post('/campionamento/utenti-disponibili', [CampionamentoController::class, 'utentiDisponibili'])->name('campionamento.utenti');
    Route::post('/campionamento/crea', [CampionamentoController::class, 'creaCampioni'])->name('campionamento.crea');

    // ============================================
    // ABILITA UID + PANEL CRUD (collegato)
    // ============================================
    Route::get('/abilita-uid', [AbilitaUidController::class, 'index'])->name('abilita.uid');
    Route::post('/abilita-uid/genera', [AbilitaUidController::class, 'store'])->name('abilita.uid.genera');

    Route::post('/panel/store', [AbilitaUidController::class, 'storePanel']);
    Route::post('/panel/update', [AbilitaUidController::class, 'updatePanel']);
    Route::delete('/panel/delete/{id}', [AbilitaUidController::class, 'deletePanel']);

    Route::post('/abilita-uid/show-data', [AbilitaUidController::class, 'showRightPanelData']);
    Route::post('/abilita-uid/enable-uids', [AbilitaUidController::class, 'enableUids']);
    Route::post('/abilita-uid/reset-iids', [AbilitaUidController::class, 'resetIids']);
    Route::post('/abilita-uid/preview-reset-iids', [AbilitaUidController::class, 'previewResetIids']);
    Route::post('/abilita-uid/search-records', [AbilitaUidController::class, 'searchRespintRecords'])
    ->name('abilita.uid.search-records');

    // ============================================
    // AUTOTEST
    // ============================================
    Route::get('/autotest', [AutotestController::class, 'index'])->name('autotest.index');
    Route::post('/autotest/start', [AutotestController::class, 'start'])->name('autotest.start');
    Route::post('/autotest/progress', [AutotestController::class, 'progress'])->name('autotest.progress');
    Route::post('/autotest/status', [AutotestController::class, 'status'])->name('autotest.status');

    // ============================================
    // CONCEPT TOOL
    // ============================================
    Route::get('/concept-tool', [ConceptToolController::class, 'index'])->name('concept.index');
    Route::post('/concept-tool', [ConceptToolController::class, 'process'])->name('concept.process');

    // ============================================
    // PANEL - Gestione Utenti
    // ============================================

        Route::get('/panelUsers', [PanelUsersController::class, 'index'])->name('panelUsers.index');
        Route::get('/panelUsers/data', [PanelUsersController::class, 'getData'])->name('panelUsers.data');
        Route::get('/panelUsers/panel-stats', [PanelUsersController::class, 'getPanelStats'])->name('panelUsers.panelStats');
        Route::post('/panelUsers/search-preview', [PanelUsersController::class, 'searchPreview'])->name('panelUsers.searchPreview');
        Route::post('/panelUsers/search-download', [PanelUsersController::class, 'searchDownload'])->name('panelUsers.searchDownload');
        Route::get('/panelUsers/inactive-summary', [PanelUsersController::class, 'getInactiveSummary'])->name('panelUsers.inactiveSummary');
        Route::get('/panelUsers/inactive-list', [PanelUsersController::class, 'getInactiveList'])->name('panelUsers.inactiveList');
        Route::get('/panelUsers/inactive-download', [PanelUsersController::class, 'downloadInactiveList'])->name('panelUsers.downloadInactiveList');
        Route::post('/panelUsers/inactive-disable', [PanelUsersController::class, 'disableInactiveUsers'])->name('panelUsers.disableInactiveUsers');
        Route::get('/panelUsers/active-summary', [PanelUsersController::class, 'getActiveSummary'])->name('panelUsers.activeSummary');

    // ============================================
    // USER actions
    // ============================================
    Route::get('/user/{user_id}', [UserProfileController::class, 'show'])->name('user.profile');

    Route::post('/user/{user_id}/deactivate', [UserProfileController::class, 'deactivate'])->name('user.deactivate');
    Route::post('/user/{user_id}/delete', [UserProfileController::class, 'delete'])->name('user.delete');
    Route::post('/user/{user_id}/activate', [UserProfileController::class, 'activate'])->name('user.activate');
    Route::post('/user/{user_id}/update-info', [UserProfileController::class, 'updateAnagrafica'])->name('user.update.info');
    Route::post('/user/{user_id}/bonus-malus', [UserProfileController::class, 'assignBonusMalus'])->name('user.bonus.malus');


    // ============================================
    // TARGET FIELD
    // ============================================
    Route::get('/fieldControl/targetField', [TargetFieldController::class, 'index'])->name('targetField.index');
    Route::get('/fieldControl/targetField/getQuestionDetail', [TargetFieldController::class, 'getQuestionDetail'])->name('targetField.getQuestionDetail');
    Route::get('/fieldControl/targetField/getTargetUIDs', [TargetFieldController::class, 'getTargetUIDs'])->name('targetField.getTargetUIDs');
    Route::get('/fieldControl/targetField/fetchTargets', [TargetFieldController::class, 'fetchTargets'])->name('targetField.fetchTargets');
    Route::post('/fieldControl/targetField/addTarget', [TargetFieldController::class, 'addTarget'])->name('targetField.addTarget');
    Route::post('/fieldControl/targetField/assignTarget', [TargetFieldController::class, 'assignTarget'])->name('targetField.assignTarget');

    // ============================================
    // PREMI PANEL
    // ============================================

    Route::get('/premi-panel', [PremiPanelController::class, 'index'])->name('premi.panel');
    Route::post('/premi-panel/paypal/{id}/pay', [PremiPanelController::class, 'payPaypal']) ->name('premi.panel.paypal.pay');
    Route::post('/premi-panel/{id}/delete', [PremiPanelController::class, 'deleteReward']) ->name('premi.panel.delete');
    Route::get('/premi-panel/data', [PremiPanelController::class, 'data'])->name('premi.panel.data');
    Route::get('/premi-panel/summary', [PremiPanelController::class, 'summary'])->name('premi.panel.summary');
    Route::post('/premi-panel/paypal/{id}/note', [PremiPanelController::class, 'savePaypalNote'])
    ->name('premi.panel.paypal.note');
    Route::post('/premi-panel/amazon/bulk-pay', [PremiPanelController::class, 'bulkPayAmazon'])
    ->name('premi.panel.amazon.bulk.pay');
    Route::get('/premi-panel/download-export/{filename}', [PremiPanelController::class, 'downloadExport'])
    ->name('premi.panel.download.export');
    Route::get('/premi-panel/paypal/export-missing-email', [PremiPanelController::class, 'exportPaypalMissingEmail'])
    ->name('premi.panel.paypal.export.missing.email');

    // ============================================
    // RECRUITMENT
    // ============================================
    Route::get('/recruitment', [RecruitmentController::class, 'index'])->name('recruitment.index');

    Route::get('/recruitment/daily', [RecruitmentController::class, 'daily'])->name('recruitment.daily');
    Route::get('/recruitment/costs', [RecruitmentController::class, 'costs'])->name('recruitment.costs');
    Route::get('/recruitment/activity', [RecruitmentController::class, 'activity'])->name('recruitment.activity');
    Route::get('/recruitment/stats', [RecruitmentController::class, 'stats'])->name('recruitment.stats');

    // ============================================
    // PRIMIS API proxy
    // ============================================
    Route::get('/primis/info', [PrimisController::class, 'getInfo']);
    Route::get('/primis/projects', [PrimisController::class, 'getProjects']);
    Route::get('/primis/projects/{projectName}/surveys/{surveyId}', [PrimisController::class, 'getSurvey']);
    Route::get('/primis/projects/{projectName}/surveys/{surveyId}/questions', [PrimisController::class, 'getQuestions']);
});
