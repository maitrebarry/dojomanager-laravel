<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\DiscipleController;
use App\Http\Controllers\Admin\DiscipleGradeController;
use App\Http\Controllers\Admin\LicenceController;
use App\Http\Controllers\Admin\SalleController;
use App\Http\Controllers\Admin\GradeController;
use App\Http\Controllers\Admin\MaitreController;
use App\Http\Controllers\Admin\LigueController;
use App\Http\Controllers\Admin\FederationController;
use App\Http\Controllers\Admin\MensualiteController;
use App\Http\Controllers\Admin\CeintureNoireController;
use App\Http\Controllers\Admin\CotisationAnnuelleController;
use App\Http\Controllers\Admin\GradePassageTariffController;
use App\Http\Controllers\Admin\GradePassageSessionController;
use App\Http\Controllers\Admin\CompetitionController;
use App\Http\Controllers\Admin\SignatureController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

/**
 * Routes publiques
 */
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::post('/language/{locale}', [LocaleController::class, 'update'])->name('language.update');

/**
 * Routes d'authentification (sans protection)
 */
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.store');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

/**
 * Routes protégées par authentification
 */
Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/logout', [AuthController::class, 'logout']);

    Route::get('/profile', [AuthController::class, 'showProfileForm'])->name('profile.edit');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::get('/change-password', [AuthController::class, 'showChangePasswordForm'])->name('password.change');
    Route::post('/change-password', [AuthController::class, 'changePassword'])->name('password.update');

    /**
     * Routes Admin (protégées par authentification et permission admin)
     */
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {

        // Tableau de bord
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Gestion des utilisateurs
        Route::patch('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
        Route::patch('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::resource('users', UserController::class);

        // Cartes de licence (libellé selon le rôle : LICENCE / RÉGIONALE / FÉDÉRALE)
        Route::get('licences/disciples', [LicenceController::class, 'disciples'])->name('licences.disciples');
        Route::get('licences/signature', [LicenceController::class, 'signatureCurrent'])->name('licences.signature');
        Route::post('licences/signature', [LicenceController::class, 'signatureSave'])->name('licences.signature.save');

        // Disciples (module cœur)
        Route::patch('disciples/{disciple}/archive', [DiscipleController::class, 'archive'])->name('disciples.archive');
        Route::patch('disciples/{disciple}/restore', [DiscipleController::class, 'restore'])->name('disciples.restore');
        Route::get('disciples/{disciple}/recu', [DiscipleController::class, 'receipt'])->name('disciples.receipt');
        Route::get('disciples/{disciple}/recu/pdf', [DiscipleController::class, 'receiptPdf'])->name('disciples.receipt.pdf');

        // Mise à jour des grades (voie directe, sans session de passage de grade —
        // cf. app/Http/Controllers/Admin/DiscipleGradeController.php)
        Route::get('disciples/grades', [DiscipleGradeController::class, 'index'])->name('disciples.grades.index');
        Route::post('disciples/grades', [DiscipleGradeController::class, 'apply'])->name('disciples.grades.apply');
        Route::post('disciples/grades/attestations', [DiscipleGradeController::class, 'attestationsSelection'])->name('disciples.grades.attestations.selection');
        Route::post('disciples/grades/candidats', [DiscipleGradeController::class, 'candidatesList'])->name('disciples.grades.candidates');
        Route::get('disciples/{disciple}/attestation-grade', [DiscipleGradeController::class, 'attestation'])->name('disciples.grades.attestation');

        Route::resource('disciples', DiscipleController::class);

        // Référentiel du club
        Route::resource('salles', SalleController::class);
        Route::resource('grades', GradeController::class);
        Route::resource('maitres', MaitreController::class);
        Route::resource('ligues', LigueController::class);
        Route::resource('federations', FederationController::class);

        // Finances — Mensualités (cotisations)
        Route::get('mensualites', [MensualiteController::class, 'index'])->name('mensualites.index');
        Route::post('mensualites/generer', [MensualiteController::class, 'generate'])->name('mensualites.generate');
        Route::post('mensualites/paiement-groupe', [MensualiteController::class, 'bulkPay'])->name('mensualites.bulk-pay');
        Route::post('mensualites/{cotisation}/payer', [MensualiteController::class, 'pay'])->name('mensualites.pay');
        Route::delete('mensualites/{cotisation}', [MensualiteController::class, 'destroy'])->name('mensualites.destroy');
        Route::get('mensualites/{cotisation}/recu', [MensualiteController::class, 'receipt'])->name('mensualites.receipt');
        Route::get('mensualites/{cotisation}/recu/pdf', [MensualiteController::class, 'receiptPdf'])->name('mensualites.receipt.pdf');

        // Finances — Ceintures noires & cotisations annuelles
        Route::resource('ceintures-noires', CeintureNoireController::class)->except(['show']);
        Route::post('cotisations-annuelles/membres/{membre}/payer', [CotisationAnnuelleController::class, 'pay'])->name('cotisations-annuelles.pay');
        Route::resource('cotisations-annuelles', CotisationAnnuelleController::class)->except(['edit', 'update']);

        // Compétition — Passages de grade
        Route::post('grade-passage-tariffs/batch', [GradePassageTariffController::class, 'storeBatch'])->name('grade-passage-tariffs.batch');
        Route::resource('grade-passage-tariffs', GradePassageTariffController::class)->except(['show']);
        // Vues fidèles à PassageGrades.jsx : Configuration / Soumission / Examen
        Route::get('grade-passages/configuration', [GradePassageSessionController::class, 'configuration'])->name('grade-passages.configuration');
        Route::get('grade-passages/soumission', [GradePassageSessionController::class, 'soumission'])->name('grade-passages.soumission');
        Route::get('grade-passages/examen', [GradePassageSessionController::class, 'examen'])->name('grade-passages.examen');
        Route::post('grade-passages/{grade_passage}/candidats/batch', [GradePassageSessionController::class, 'addCandidatesBatch'])->name('grade-passages.candidats.batch');
        Route::post('grade-passages/{grade_passage}/candidats/{candidate}/notes', [GradePassageSessionController::class, 'saveExamNotes'])->name('grade-passages.candidats.notes');
        Route::post('grade-passages/{grade_passage}/candidats', [GradePassageSessionController::class, 'addCandidate'])->name('grade-passages.candidats.add');
        Route::delete('grade-passages/{grade_passage}/candidats/{candidate}', [GradePassageSessionController::class, 'removeCandidate'])->name('grade-passages.candidats.remove');
        Route::post('grade-passages/{grade_passage}/candidats/{candidate}/payer', [GradePassageSessionController::class, 'pay'])->name('grade-passages.candidats.pay');
        Route::post('grade-passages/{grade_passage}/candidats/{candidate}/evaluer', [GradePassageSessionController::class, 'evaluate'])->name('grade-passages.candidats.evaluate');
        Route::get('grade-passages/{grade_passage}/candidats/{candidate}/attestation', [GradePassageSessionController::class, 'attestation'])->name('grade-passages.candidats.attestation');
        Route::post('grade-passages/{grade_passage}/finaliser', [GradePassageSessionController::class, 'finalize'])->name('grade-passages.finalize');
        Route::resource('grade-passages', GradePassageSessionController::class);

        // Compétitions & combats
        Route::post('competitions/{competition}/combats', [CompetitionController::class, 'addCombat'])->name('competitions.combats.add');
        Route::delete('competitions/{competition}/combats/{combat}', [CompetitionController::class, 'removeCombat'])->name('competitions.combats.remove');
        Route::resource('competitions', CompetitionController::class);

        // Signatures officielles
        Route::resource('signatures', SignatureController::class)->except(['show']);

        // Paramètres
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

        // Permissions
        Route::post('/permissions', [SettingsController::class, 'storePermission'])->name('permissions.store');
        Route::put('/permissions/{permission}', [SettingsController::class, 'updatePermission'])->name('permissions.update');
        Route::delete('/permissions/{permission}', [SettingsController::class, 'destroyPermission'])->name('permissions.destroy');
        Route::post('/permissions/assign', [SettingsController::class, 'assignPermissions'])->name('permissions.assign');
    });

    /**
     * Redirection dashboard générique
     */
    Route::get('/dashboard', function () {
        return redirect()->route('admin.dashboard');
    })->name('dashboard');
});
