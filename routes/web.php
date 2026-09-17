<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\CandidateAdminController;
use App\Http\Controllers\Admin\ExamEventAdminController;
use App\Http\Controllers\Admin\ExamMonitorController;
use App\Http\Controllers\Admin\QuestionAdminController;
use App\Http\Controllers\Admin\RoundAdminController;
use App\Http\Controllers\Admin\SpeakingAdminController;
use App\Http\Controllers\Candidate\CandidateAuthController;
use App\Http\Controllers\Candidate\CandidateDashboardController;
use App\Http\Controllers\Candidate\ExamController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));
Route::get('/login', [CandidateAuthController::class, 'showLogin'])->name('login');

Route::prefix('candidate')->name('candidate.')->group(function () {
    Route::get('/login', [CandidateAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [CandidateAuthController::class, 'login'])->name('login.submit');

    Route::middleware('candidate.session')->group(function () {
        Route::get('/', CandidateDashboardController::class)->name('dashboard');
        Route::post('/logout', [CandidateAuthController::class, 'logout'])->name('logout');
        Route::post('/rounds/{round}/start', [ExamController::class, 'start'])->name('exam.start');
        Route::get('/rounds/{round}/attempts/{attempt}', [ExamController::class, 'show'])->name('exam.show');
        Route::get('/rounds/{round}/attempts/{attempt}/completed', [ExamController::class, 'completed'])->name('exam.completed');
        Route::post('/rounds/{round}/attempts/{attempt}/answer', [ExamController::class, 'saveAnswer'])->name('exam.answer');
        Route::post('/rounds/{round}/attempts/{attempt}/submit', [ExamController::class, 'submit'])->name('exam.submit');
    });
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');

    Route::middleware(['auth','admin.role'])->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::get('/monitor', ExamMonitorController::class)->name('monitor');

        Route::get('/events', [ExamEventAdminController::class, 'index'])->name('events.index');
        Route::post('/events', [ExamEventAdminController::class, 'store'])->name('events.store');
        Route::put('/events/{event}', [ExamEventAdminController::class, 'update'])->name('events.update');
        Route::delete('/events/{event}', [ExamEventAdminController::class, 'destroy'])->name('events.destroy');

        Route::get('/candidates', [CandidateAdminController::class, 'index'])->name('candidates.index');
        Route::post('/candidates', [CandidateAdminController::class, 'store'])->name('candidates.store');
        Route::post('/candidates/bulk-import', [CandidateAdminController::class, 'bulkImport'])->name('candidates.bulk');
        Route::get('/candidates/export-excel', [CandidateAdminController::class, 'exportExcel'])->name('candidates.export-excel');
        Route::get('/candidates/excel-template', [CandidateAdminController::class, 'downloadTemplate'])->name('candidates.excel-template');
        Route::post('/candidates/import-excel', [CandidateAdminController::class, 'importExcel'])->name('candidates.import-excel');
        Route::get('/candidates/{candidate}', [CandidateAdminController::class, 'show'])->name('candidates.show');
        Route::post('/candidates/{candidate}/allow-exam', [CandidateAdminController::class, 'allowExam'])->name('candidates.allow-exam');
        Route::post('/candidates/{candidate}/lock-exam', [CandidateAdminController::class, 'lockExam'])->name('candidates.lock-exam');
        Route::post('/candidates/{candidate}/rounds/{round}/retake', [CandidateAdminController::class, 'retake'])->name('candidates.retake');
        Route::put('/candidates/{candidate}', [CandidateAdminController::class, 'update'])->name('candidates.update');
        Route::delete('/candidates/{candidate}', [CandidateAdminController::class, 'destroy'])->name('candidates.destroy');

        Route::get('/speaking', [SpeakingAdminController::class, 'index'])->name('speaking.index');
        Route::post('/speaking/candidates', [SpeakingAdminController::class, 'addCandidate'])->name('speaking.add');
        Route::post('/speaking/{session}/score', [SpeakingAdminController::class, 'score'])->name('speaking.score');

        Route::get('/questions', [QuestionAdminController::class, 'index'])->name('questions.index');
        Route::get('/rounds/{round}/preview', [QuestionAdminController::class, 'preview'])->name('questions.preview');
        Route::get('/questions/excel-template', [QuestionAdminController::class, 'downloadTemplate'])->name('questions.excel-template');
        Route::get('/questions/export-excel', [QuestionAdminController::class, 'exportExcel'])->name('questions.export-excel');
        Route::post('/questions/import-excel', [QuestionAdminController::class, 'importExcel'])->name('questions.import-excel');
        Route::post('/questions', [QuestionAdminController::class, 'store'])->name('questions.store');
        Route::delete('/questions/bulk', [QuestionAdminController::class, 'bulkDestroy'])->name('questions.bulk-destroy');
        Route::put('/questions/{question}', [QuestionAdminController::class, 'update'])->name('questions.update');
        Route::delete('/questions/{question}', [QuestionAdminController::class, 'destroy'])->name('questions.destroy');
        Route::post('/questions/{question}/assign', [QuestionAdminController::class, 'assign'])->name('questions.assign');

        Route::post('/rounds/open-all', [RoundAdminController::class, 'openAll'])->name('rounds.open-all');
        Route::post('/rounds/close-all', [RoundAdminController::class, 'closeAll'])->name('rounds.close-all');
        Route::post('/rounds/{round}/open', [RoundAdminController::class, 'open'])->name('rounds.open');
        Route::post('/rounds/{round}/close', [RoundAdminController::class, 'close'])->name('rounds.close');
        Route::post('/rounds/{round}/quota', [RoundAdminController::class, 'updateQuota'])->name('rounds.quota');
        Route::get('/rounds/{round}/results', [RoundAdminController::class, 'results'])->name('rounds.results');
        Route::post('/rounds/{round}/advance', [RoundAdminController::class, 'advance'])->name('rounds.advance');
    });
});
