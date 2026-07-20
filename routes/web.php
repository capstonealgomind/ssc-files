<?php

use App\Http\Controllers\AivaChatController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BallotReceiptController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\IdScanController;
use App\Http\Controllers\Auth\RegistrationStatusController;
use App\Http\Controllers\Auth\RegistrationSuccessController;
use App\Http\Controllers\CheckStatusController;
use App\Http\Controllers\ReactivationController;
use App\Http\Controllers\AdminReactivationController;
use App\Http\Controllers\VoterPageController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\VoterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\CommitteeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\FaqChatController;
use App\Http\Controllers\LocationGateController;
use App\Http\Controllers\LiveStandingController;
use App\Http\Controllers\MapTileController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RegistrationAttemptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\AdminSupportController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\SupportTicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/location', [LocationGateController::class, 'show'])->name('location.show');
Route::post('/location/verify', [LocationGateController::class, 'verify'])->name('location.verify');

Route::get('/', [WelcomeController::class, 'index'])->name('home');
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/live-standing', [LiveStandingController::class, 'index'])->name('live-standing');
Route::post('/aiva/chat', [AivaChatController::class, 'store'])
    ->middleware('throttle:12,1')
    ->name('aiva.chat');

Route::get('/check-status', [CheckStatusController::class, 'show'])->name('check-status');
Route::post('/check-status', [CheckStatusController::class, 'check'])->name('check-status.check');

Route::get('/reactivate', [ReactivationController::class, 'create'])->name('reactivate');
Route::post('/reactivate/validate', [ReactivationController::class, 'validateVoter'])->name('reactivate.validate');
Route::get('/reactivate/validate', fn () => redirect()->route('reactivate'));
Route::post('/reactivate', [ReactivationController::class, 'store'])->name('reactivate.store');
Route::get('/reactivation-status', [ReactivationController::class, 'statusForm'])->name('reactivation-status');
Route::post('/reactivation-status', [ReactivationController::class, 'statusCheck'])->name('reactivation-status.check');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
    Route::get('/register/id-scan', [IdScanController::class, 'create'])->name('register.id-scan');
    Route::post('/register/id-scan', [IdScanController::class, 'store'])->name('register.id-scan.store');
    Route::get('/register/success', [RegistrationSuccessController::class, 'show'])->name('register.success');
    Route::get('/email/verify/{token}', [EmailVerificationController::class, 'verify'])->name('email.verify');
    Route::get('/register/verify-otp', [OtpController::class, 'create'])->name('register.verify-otp');
    Route::post('/register/verify-otp', [OtpController::class, 'store'])->name('register.verify-otp.store');
    Route::post('/register/resend-otp', [OtpController::class, 'resend'])->name('register.resend-otp');

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::get('/ballot-receipt/{receipt}/pdf', [BallotReceiptController::class, 'pdf'])
    ->name('ballot-receipt.pdf')
    ->middleware('signed');

Route::middleware('auth')->group(function () {
    Route::get('/registration-status', [RegistrationStatusController::class, 'show'])->name('registration.status');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/name', [ProfileController::class, 'updateName'])->name('profile.name');
    Route::get('/elections', function (Request $request) {
        if ($request->user()->role === 'admin') {
            return app(ElectionController::class)->index();
        }

        if ($request->user()->role === 'committee') {
            return redirect()->route('committee');
        }

        return app(VoteController::class)->index($request);
    })->name('elections');
    Route::redirect('/vote', '/elections');
    Route::post('/elections/{election}/cast-vote', [VoteController::class, 'store'])->name('elections.cast-vote');
    Route::get('/ballot-submissions/{submission}/status', [VoteController::class, 'submissionStatus'])
        ->name('ballot-submissions.status');
    Route::get('/ballot-receipt/{receipt}', [BallotReceiptController::class, 'show'])->name('ballot-receipt.show');
    Route::post('/elections', [ElectionController::class, 'store'])->middleware('admin')->name('elections.store');
    Route::put('/elections/{election}', [ElectionController::class, 'update'])->middleware('admin')->name('elections.update');
    Route::delete('/elections/{election}', [ElectionController::class, 'destroy'])->middleware('admin')->name('elections.destroy');
    Route::get('/candidates', [CandidateController::class, 'index'])->middleware('admin')->name('candidates');
    Route::get('/candidates/create', [CandidateController::class, 'create'])->middleware('admin')->name('candidates.create');
    Route::post('/candidates', [CandidateController::class, 'store'])->middleware('admin')->name('candidates.store');
    Route::get('/candidates/{candidate}/edit', [CandidateController::class, 'edit'])->middleware('admin')->name('candidates.edit');
    Route::put('/candidates/{candidate}', [CandidateController::class, 'update'])->middleware('admin')->name('candidates.update');
    Route::delete('/candidates/{candidate}', [CandidateController::class, 'destroy'])->middleware('admin')->name('candidates.destroy');
    Route::get('/committee', [CommitteeController::class, 'index'])->middleware('committee')->name('committee');
    Route::post('/committee/candidates', [CommitteeController::class, 'store'])->middleware('committee')->name('committee.candidates.store');
    Route::get('/my-votes', [VoterPageController::class, 'myVotes'])->name('my-votes');
    Route::get('/results', [VoterPageController::class, 'results'])->name('results');
    Route::get('/announcements', [VoterPageController::class, 'announcements'])->name('announcements');
    Route::get('/announcements/manage', [AnnouncementController::class, 'index'])->middleware('admin')->name('announcements.manage');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->middleware('admin')->name('announcements.store');
    Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update'])->middleware('admin')->name('announcements.update');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->middleware('admin')->name('announcements.destroy');
    Route::get('/help', [SupportTicketController::class, 'index'])->name('help');
    Route::post('/help/tickets', [SupportTicketController::class, 'store'])->name('help.tickets.store');
    Route::get('/help/tickets/{ticket}', [SupportTicketController::class, 'show'])->name('help.tickets.show');
    Route::post('/help/tickets/{ticket}/messages', [SupportTicketController::class, 'storeMessage'])->name('help.tickets.messages.store');
    Route::get('/faq', [VoterPageController::class, 'faq'])->name('faq');
    Route::post('/faq/chat', [FaqChatController::class, 'store'])->middleware('throttle:15,1')->name('faq.chat');
    Route::get('/support', [AdminSupportController::class, 'index'])->middleware('admin')->name('support');
    Route::get('/support/tickets/{ticket}', [AdminSupportController::class, 'show'])->middleware('admin')->name('support.tickets.show');
    Route::post('/support/tickets/{ticket}/approve', [AdminSupportController::class, 'approve'])->middleware('admin')->name('support.tickets.approve');
    Route::post('/support/tickets/{ticket}/reject', [AdminSupportController::class, 'reject'])->middleware('admin')->name('support.tickets.reject');
    Route::post('/support/tickets/{ticket}/close', [AdminSupportController::class, 'close'])->middleware('admin')->name('support.tickets.close');
    Route::post('/support/tickets/{ticket}/messages', [AdminSupportController::class, 'storeMessage'])->middleware('admin')->name('support.tickets.messages.store');
    Route::get('/voters', [VoterController::class, 'index'])->name('voters');
    Route::get('/voters/{voter}', [VoterController::class, 'show'])->middleware('admin')->name('voters.show');
    Route::post('/voters/{voter}/verify', [VoterController::class, 'verify'])->middleware('admin')->name('voters.verify');
    Route::post('/voters/{voter}/reject', [VoterController::class, 'reject'])->middleware('admin')->name('voters.reject');
    Route::post('/voters/{voter}/rerun-ocr', [VoterController::class, 'rerunOcr'])->middleware('admin')->name('voters.rerun-ocr');
    Route::delete('/voters/{voter}', [VoterController::class, 'destroy'])->middleware('admin')->name('voters.destroy');
    Route::get('/reactivation-requests', [AdminReactivationController::class, 'index'])->middleware('admin')->name('reactivation-requests');
    Route::post('/reactivation-requests/{reactivationRequest}/process', [AdminReactivationController::class, 'process'])
        ->middleware('admin')
        ->name('reactivation-requests.process');
    Route::get('/monitoring', [MonitoringController::class, 'index'])->middleware('admin')->name('monitoring');
    Route::get('/reports', [ReportController::class, 'index'])->middleware('admin')->name('reports');
    Route::get('/reports/{election}/export/{type}/pdf', [ReportController::class, 'exportPdf'])
        ->middleware('admin')
        ->name('reports.export.pdf');
    Route::get('/reports/{election}/export/{type}/excel', [ReportController::class, 'exportExcel'])
        ->middleware('admin')
        ->name('reports.export.excel');
    Route::get('/settings', [SettingsController::class, 'index'])->middleware('admin')->name('settings');
    Route::get('/system', [SystemController::class, 'index'])->middleware('admin')->name('system');
    Route::post('/presence/heartbeat', [SystemController::class, 'heartbeat'])->name('presence.heartbeat');
    Route::post('/settings/departments', [SettingsController::class, 'storeDepartment'])->middleware('admin')->name('settings.departments.store');
    Route::put('/settings/departments/{department}', [SettingsController::class, 'updateDepartment'])->middleware('admin')->name('settings.departments.update');
    Route::delete('/settings/departments/{department}', [SettingsController::class, 'destroyDepartment'])->middleware('admin')->name('settings.departments.destroy');
    Route::post('/settings/courses', [SettingsController::class, 'storeCourse'])->middleware('admin')->name('settings.courses.store');
    Route::put('/settings/courses/{course}', [SettingsController::class, 'updateCourse'])->middleware('admin')->name('settings.courses.update');
    Route::delete('/settings/courses/{course}', [SettingsController::class, 'destroyCourse'])->middleware('admin')->name('settings.courses.destroy');
    Route::post('/settings/year-levels', [SettingsController::class, 'storeYearLevel'])->middleware('admin')->name('settings.year-levels.store');
    Route::put('/settings/year-levels/{yearLevel}', [SettingsController::class, 'updateYearLevel'])->middleware('admin')->name('settings.year-levels.update');
    Route::delete('/settings/year-levels/{yearLevel}', [SettingsController::class, 'destroyYearLevel'])->middleware('admin')->name('settings.year-levels.destroy');
    Route::post('/settings/positions', [SettingsController::class, 'storePosition'])->middleware('admin')->name('settings.positions.store');
    Route::put('/settings/positions/{position}', [SettingsController::class, 'updatePosition'])->middleware('admin')->name('settings.positions.update');
    Route::delete('/settings/positions/{position}', [SettingsController::class, 'destroyPosition'])->middleware('admin')->name('settings.positions.destroy');
    Route::post('/settings/partylists', [SettingsController::class, 'storePartylist'])->middleware('admin')->name('settings.partylists.store');
    Route::put('/settings/partylists/{partylist}', [SettingsController::class, 'updatePartylist'])->middleware('admin')->name('settings.partylists.update');
    Route::delete('/settings/partylists/{partylist}', [SettingsController::class, 'destroyPartylist'])->middleware('admin')->name('settings.partylists.destroy');
    Route::put('/settings/location-range', [SettingsController::class, 'updateLocationRange'])->middleware('admin')->name('settings.location-range.update');
    Route::put('/settings/dts-registration', [SettingsController::class, 'updateDtsRegistration'])->middleware('admin')->name('settings.dts-registration.update');
    Route::put('/settings/ua-management', [SettingsController::class, 'updateUaManagement'])->middleware('admin')->name('settings.ua-management.update');
    Route::put('/settings/school-year', [SettingsController::class, 'updateSchoolYear'])->middleware('admin')->name('settings.school-year.update');
    Route::post('/settings/ssc-members', [SettingsController::class, 'storeSscMembers'])->middleware('admin')->name('settings.ssc-members.store');
    Route::delete('/settings/ssc-members', [SettingsController::class, 'destroyAllSscMembers'])->middleware('admin')->name('settings.ssc-members.destroy-all');
    Route::delete('/settings/ssc-members/{sscMemberImage}', [SettingsController::class, 'destroySscMember'])->middleware('admin')->name('settings.ssc-members.destroy');
    Route::post('/settings/gallery', [SettingsController::class, 'storeGalleryImages'])->middleware('admin')->name('settings.gallery.store');
    Route::put('/settings/gallery/style', [SettingsController::class, 'updateGalleryStyle'])->middleware('admin')->name('settings.gallery.style.update');
    Route::delete('/settings/gallery', [SettingsController::class, 'destroyAllGalleryImages'])->middleware('admin')->name('settings.gallery.destroy-all');
    Route::delete('/settings/gallery/{galleryImage}', [SettingsController::class, 'destroyGalleryImage'])->middleware('admin')->name('settings.gallery.destroy');
    Route::get('/map-tiles/{z}/{x}/{y}.png', [MapTileController::class, 'show'])->middleware('admin')->name('map-tiles.show');
    Route::get('/accounts', [AccountController::class, 'index'])->middleware('admin')->name('accounts');
    Route::post('/accounts', [AccountController::class, 'store'])->middleware('admin')->name('accounts.store');
    Route::post('/accounts/committee', [AccountController::class, 'storeCommittee'])->middleware('admin')->name('accounts.committee.store');
    Route::put('/accounts/{user}', [AccountController::class, 'update'])->middleware('admin')->name('accounts.update');
    Route::delete('/accounts/{user}', [AccountController::class, 'destroy'])->middleware('admin')->name('accounts.destroy');
    Route::delete('/accounts/committee/{user}', [AccountController::class, 'destroyCommittee'])->middleware('admin')->name('accounts.committee.destroy');
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('admin')->name('audit-logs');
    Route::get('/registration-attempts', [RegistrationAttemptController::class, 'index'])->middleware('admin')->name('registration-attempts');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
