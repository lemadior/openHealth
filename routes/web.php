<?php

declare(strict_types=1);

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Actions\Logout;
use App\Livewire\Auth\VerifyEmail;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Patient\PatientComponent;
use App\Livewire\License\LicenseShow;
use Illuminate\Support\Facades\Route;
use App\Livewire\License\LicenseIndex;
use App\Livewire\Patient\PatientIndex;
use App\Livewire\Contract\ContractForm;
use App\Livewire\Division\DivisionForm;
use App\Livewire\Employee\EmployeeEdit;
use App\Livewire\Auth\SelectLegalEntity;
use App\Livewire\Contract\ContractIndex;
use App\Livewire\Employee\EmployeeIndex;
use App\Http\Controllers\HomeController;
use App\Livewire\Division\DivisionIndex;
use App\Http\Controllers\EmailController;
use App\Livewire\Employee\EmployeeCreate;
use App\Livewire\Encounter\EncounterEdit;
use App\Livewire\Encounter\EncounterCreate;
use App\Livewire\License\Forms\LicenseForms;
use App\Livewire\LegalEntity\EditLegalEntity;
use App\Livewire\Patient\Records\PatientData;
use App\Livewire\Declaration\DeclarationIndex;
use App\Livewire\LegalEntity\CreateLegalEntity;
use App\Livewire\Patient\Records\PatientSummary;
use App\Livewire\Division\HealthcareServiceForm;
use App\Livewire\License\Forms\CreateNewLicense;
use App\Livewire\Patient\Records\PatientEpisodes;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Livewire\DiagnosticReport\DiagnosticReportCreate;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [HomeController::class, 'index'])->name('home.index');
Route::post('/send-email', [EmailController::class, 'sendEmail'])->name('send.email');

/* Auth */

Route::get('/ehealth/oauth/', [Login::class, 'callback'])->name('ehealth.oauth.callback');

Route::middleware('guest')->group(function () {
    Route::get('login', Login::class)->name('login');
    Route::get('register', Register::class)->name('register');
    Route::get('forgot-password', ForgotPassword::class)->name('forgot.password');
    Route::get('reset-password/{token}', ResetPassword::class)->name('password.reset');

    Route::get('verify-email', VerifyEmail::class)->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');
});

Route::post('logout', Logout::class)->name('logout');

/* Dashboard */

Route::middleware(['auth:web,ehealth', 'verified'])->group(function () {

    Route::get('/page-not-found', function () {
        return view('errors.404');
    })->name('url.page-not-found');

    Route::get('/select-legal-entity', SelectLegalEntity::class)->name('legalEntity.select');

    /*
     * Routes for authenticated users WITHOUT legal_entity_id in the URL.
     * This group of routes is intended for pages accessible to authenticated users,
     * which do NOT require the presence of legal_entity_id in the URL segment.
     * This includes, for example, initial setup pages for new users.
     * They still have the prefix /dashboard, but do not have {legal_entity_id} or the middleware resolve.legal.entity.
     */
    Route::middleware(['auth:web'])->prefix('dashboard')->group(function () {
        Route::get('/', fn() => view('dashboard'))->name('dashboard');
        Route::get('/legal-entities/create', CreateLegalEntity::class)->name('create.legalEntities');
    });

    /* Common routes to work with selected Legal Entity */
    Route::middleware('resolve.legal.entity')->prefix('/{legal_entity_id}')->group(function () {
        Route::group(['middleware' => ['role:OWNER|ADMIN|DOCTOR'], 'prefix' => '/dashboard'], function () {
            Route::get('/', fn() => view('dashboard'))->name('dashboard');

            Route::prefix('legal-entities')->group(function () {
                Route::get('/edit', EditLegalEntity::class)->name('edit.legalEntities');
                // Route::get('/create', CreateLegalEntity::class)->name('create.legalEntities');
            });

            Route::prefix('division')->group(function () {
                Route::get('/', DivisionIndex::class)->name('division.index');
                Route::get('/form/{id?}', DivisionForm::class)->name('division.form');
                Route::get('/{division}/healthcare-service', HealthcareServiceForm::class)->name('healthcare_service.index');
            });

            Route::prefix('employee')->group(function () {
                Route::get('/', EmployeeIndex::class)->name('employee.index');
                Route::get('/{id}', EmployeeEdit::class)
                    ->name('employee.edit')
                    ->where('id', '[0-9]+');
                Route::get('/new', EmployeeCreate::class)->name('employee.create');
            });

            Route::prefix('contract')->group(function () {
                Route::get('/', ContractIndex::class)->name('contract.index');
                Route::get('/form/{id?}', ContractForm::class)->name('contract.form');
            });

            Route::prefix('license')->group(function () {
                Route::get('/', LicenseIndex::class)->name('license.index');
                Route::get('/update/{id}', LicenseForms::class)->name('license.form');
                Route::get('/create', CreateNewLicense::class)->name('license.create');
                Route::get('/show/{id}', LicenseShow::class)->name('license.show');
            });

            Route::prefix('declaration')->group(function () {
                Route::get('/', DeclarationIndex::class)->name('declaration.index');
            });
        });

    Route::group(['middleware' => ['role:OWNER|ADMIN|DOCTOR']], static function () {
        Route::prefix('patient')->group(static function () {
            Route::get('/', PatientIndex::class)->name('patient.index');
            Route::get('/create/{patientId?}', PatientComponent::class)->name('patient.form');
            Route::get('/{patientId}/patient-data', PatientData::class)->name('patient.patient-data');
            Route::get('/{patientId}/summary', PatientSummary::class)->name('patient.summary');
            Route::get('/{patientId}/episodes', PatientEpisodes::class)->name('patient.episodes');

                Route::get('/{patientId}/encounter/create', EncounterCreate::class)->name('encounter.create');
                Route::get('/{patientId}/encounter/{encounterId}', EncounterEdit::class)->name('encounter.edit');

                Route::get('/{patientId}/diagnostic-report/create', DiagnosticReportCreate::class)->name('diagnostic-report.create');
            });
        });
    });

    /**
     * This route /any is now inside a group protected by 'auth' and 'verified'.
     * It will work for any URL that was not found above, but only for authenticated users.
     * This will include URLs that do not have the /{legal_entity_id}/ prefix.
     * The route intercept invalid URLs of any types and redirect it to display only error page.
     */
    Route::get('/{any}', function () {
       return redirect()->route('url.page-not-found');
    })->where('any', '.*')->name('url.not-found');;
});

/*
 * GLOBAL FALLBACK ROUTE (MUST BE LAST IN web.php)
 * This Route::fallback() will trigger for ANY request that has not been matched by any route above.
 * This is final 404 handler for both authenticated and unauthenticated users,
 * or for routes that simply do not fit into any structured groups.
 */
Route::fallback(function () {
    return redirect()->route('url.page-not-found');
});
