<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\StudentExamController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\ExamShareController;

// Student (public) routes
Route::get('/', [StudentExamController::class, 'showCodeEntry'])->name('student.entry');
Route::post('/verify', [StudentExamController::class, 'verifyCode'])->name('student.verify');
Route::post('/start/{code}', [StudentExamController::class, 'startExam'])->name('student.start');
Route::get('/exam/{code}', [StudentExamController::class, 'resumeExam'])->name('student.exam');
Route::post('/answer/{attemptId}', [StudentExamController::class, 'saveAnswer'])->name('student.save-answer');
Route::post('/proctor/{attemptId}', [StudentExamController::class, 'logIncident'])->name('student.proctor-log');
Route::get('/exam-status/{attemptId}', [StudentExamController::class, 'examStatus'])->name('student.exam-status');
Route::post('/submit/{code}', [StudentExamController::class, 'submitExam'])->name('student.submit');
Route::get('/results/{code}', [StudentExamController::class, 'showResults'])->name('student.results');

// Teacher auth routes
Route::get('/login', [LoginController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Teacher (authenticated) routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/exams-components-by-type', [ExamController::class, 'componentsByType'])->name('exams.components-by-type');
    Route::resource('exams', ExamController::class);
    Route::post('/exams/{exam}/questions', [ExamController::class, 'storeQuestion'])->name('exams.questions.store');
    Route::put('/exams/{exam}/questions/{question}', [ExamController::class, 'updateQuestion'])->name('exams.questions.update');
    Route::delete('/exams/{exam}/questions/{question}', [ExamController::class, 'destroyQuestion'])->name('exams.questions.destroy');
    Route::delete('/exams/{exam}/questions', [ExamController::class, 'destroyAllQuestions'])->name('exams.questions.destroy-all');
    Route::post('/exams/{exam}/import-questions', [ExamController::class, 'importQuestions'])->name('exams.import-questions');
    Route::get('/exams/{exam}/question-bank', [ExamController::class, 'questionBank'])->name('exams.question-bank');
    Route::post('/exams/{exam}/question-bank/import', [ExamController::class, 'importFromBank'])->name('exams.question-bank.import');
    Route::post('/exams/{exam}/generate-codes', [ExamController::class, 'generateCodes'])->name('exams.generate-codes');
    Route::post('/exams/{exam}/generate-code-student', [ExamController::class, 'generateCodeForStudent'])->name('exams.generate-code-student');
    Route::post('/exams/{exam}/regenerate-codes', [ExamController::class, 'regenerateCodes'])->name('exams.regenerate-codes');
    Route::post('/exams/{exam}/codes/{code}/extra-time', [ExamController::class, 'setExtraTime'])->name('exams.codes.extra-time');
    Route::get('/exams/{exam}/section-students/{section}', [ExamController::class, 'sectionStudents'])->name('exams.section-students');
    Route::get('/exams/{exam}/preview', [ExamController::class, 'preview'])->name('exams.preview');
    Route::post('/exams/{exam}/clone', [ExamController::class, 'cloneExam'])->name('exams.clone');
    Route::post('/exams/{exam}/archive', [ExamController::class, 'archive'])->name('exams.archive');
    Route::post('/exams/{exam}/unarchive', [ExamController::class, 'unarchive'])->name('exams.unarchive');
    Route::get('/exams/{exam}/results', [ExamController::class, 'results'])->name('exams.results');
    Route::get('/exams/{exam}/monitor', [ExamController::class, 'monitor'])->name('exams.monitor');
    Route::get('/exams/{exam}/monitor/data', [ExamController::class, 'monitorData'])->name('exams.monitor-data');
    Route::post('/exams/{exam}/sync-grades', [ExamController::class, 'syncGrades'])->name('exams.sync-grades');
    Route::get('/exams/{exam}/results/export', [ExamController::class, 'exportResults'])->name('exams.results.export');
    Route::get('/exams/{exam}/codes/pdf', [ExamController::class, 'exportCodesPdf'])->name('exams.codes.pdf');
    Route::patch('/exams/{exam}/questions/reorder', [ExamController::class, 'reorderQuestions'])->name('exams.questions.reorder');
    Route::get('/exams/{exam}/attempts/{attempt}', [ExamController::class, 'attemptDetail'])->name('exams.attempt-detail');
    Route::get('/exams/{exam}/attempts/{attempt}/pdf', [ExamController::class, 'attemptPdf'])->name('exams.attempt-pdf');
    Route::post('/exams/{exam}/attempts/{attempt}/close', [ExamController::class, 'closeAttempt'])->name('exams.close-attempt');
    Route::post('/exams/{exam}/attempts/{attempt}/resume', [ExamController::class, 'resumePausedAttempt'])->name('exams.resume-attempt');
    Route::post('/exams/{exam}/attempts/{attempt}/answers/{answer}/grade', [ExamController::class, 'gradeAnswer'])->name('exams.grade-answer');
    Route::get('/templates/questions', [TemplateController::class, 'downloadQuestionsTemplate'])->name('templates.questions');

    // Exam sharing
    Route::get('/share-teachers/search', [ExamShareController::class, 'searchTeachers'])->name('shares.search-teachers');
    Route::post('/exams/{exam}/share', [ExamShareController::class, 'store'])->name('exams.share');
    Route::get('/shared-with-me', [ExamShareController::class, 'index'])->name('shares.index');
    Route::post('/shares/{share}/accept', [ExamShareController::class, 'accept'])->name('shares.accept');
    Route::post('/shares/{share}/reject', [ExamShareController::class, 'reject'])->name('shares.reject');
});
