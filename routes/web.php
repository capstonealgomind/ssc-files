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
use App\Http\Controllers\GuideController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\VoterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DisabledAccountController;
use App\Http\Controllers\AdminDisabledAccountController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\CommitteeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\FaqChatController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\LiveStandingController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RegistrationAttemptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\AdminSupportController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\SupportTicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [WelcomeController::class, 'index'])->name('home');
Route::get('/storage/{folder}/{file}', function (string $folder, string $file) {
    $disk = \Illuminate\Support\Facades\Storage::disk('public');
    $path = $folder.'/'.$file;
    abort_unless($disk->exists($path), 404);

    return $disk->response($path);
})->whereIn('folder', ['announcements', 'candidates', 'gallery', 'profile-photos', 'ssc-members'])
    ->where('file', '[A-Za-z0-9_-]+\.[A-Za-z0-9]+');
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/guide', [GuideController::class, 'index'])->name('guide');
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
    Route::get('/register/check-name', [RegisterController::class, 'checkName'])
        ->middleware('throttle:60,1')
        ->name('register.check-name');
    Route::get('/register/check-email', [RegisterController::class, 'checkEmail'])
        ->middleware('throttle:60,1')
        ->name('register.check-email');
    Route::get('/register/details', [RegisterController::class, 'create'])->name('register.details');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,1')->block()->name('register.store');
    Route::get('/register/id-scan', [IdScanController::class, 'create'])->name('register.id-scan');
    Route::get('/register/id-scan/preview', [IdScanController::class, 'preview'])->name('register.id-scan.preview');
    Route::post('/register/reset', [IdScanController::class, 'reset'])->block(90, 15)->name('register.reset');
    Route::post('/register/id-scan', [IdScanController::class, 'store'])->middleware('throttle:8,1')->block(90, 15)->name('register.id-scan.store');
    Route::get('/register/success', [RegistrationSuccessController::class, 'show'])->name('register.success');
    Route::get('/email/verify/{token}', [EmailVerificationController::class, 'verify'])->name('email.verify');
    Route::controller(\App\Http\Controllers\Auth\OfficialRegistrationOtpController::class)->group(function () {
        Route::get('/register/verify-otp', 'create')->name('register.verify-otp');
        Route::post('/register/verify-otp', 'store')->middleware('throttle:15,1')->block()->name('register.verify-otp.store');
        Route::post('/register/resend-otp', 'resend')->middleware('throttle:3,1')->block()->name('register.resend-otp');
        Route::post('/register/update-email', 'updateEmail')->middleware('throttle:3,1')->block()->name('register.update-email');
        Route::get('/register/decision', 'decision')->name('register.decision');
    });

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/forgot-password/verify', [ForgotPasswordController::class, 'otpForm'])->name('password.otp');
    Route::post('/forgot-password/verify', [ForgotPasswordController::class, 'verifyOtp'])
        ->middleware('throttle:10,1')
        ->name('password.otp.store');
    Route::post('/forgot-password/resend', [ForgotPasswordController::class, 'resend'])
        ->middleware('throttle:5,1')
        ->name('password.otp.resend');
    Route::get('/forgot-password/reset', [ForgotPasswordController::class, 'resetForm'])->name('password.reset');
    Route::post('/forgot-password/reset', [ForgotPasswordController::class, 'reset'])->name('password.update');
});

Route::get('/ballot-receipt/{receipt}/pdf', [BallotReceiptController::class, 'pdf'])
    ->name('ballot-receipt.pdf')
    ->middleware('signed');

Route::middleware(['auth', 'voter.not-expired'])->group(function () {
    Route::get('/account-disabled', [DisabledAccountController::class, 'show'])->name('account.disabled');
    Route::get('/account-disabled/appeal', [DisabledAccountController::class, 'appealForm'])->name('account.disabled.appeal');
    Route::post('/account-disabled/appeal', [DisabledAccountController::class, 'submitAppeal'])->name('account.disabled.appeal.store');
    Route::get('/registration-status', [RegistrationStatusController::class, 'show'])->name('registration.status');
    Route::get('/system-notifications', [\App\Http\Controllers\AdminSystemNotificationController::class, 'index'])
        ->middleware('admin')->name('system-notifications.index');
    Route::patch('/system-notifications/{notification}/read', [\App\Http\Controllers\AdminSystemNotificationController::class, 'markRead'])
        ->middleware('admin')->name('system-notifications.read');
    Route::patch('/system-notifications/read-all', [\App\Http\Controllers\AdminSystemNotificationController::class, 'markAllRead'])
        ->middleware('admin')->name('system-notifications.read-all');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/search', GlobalSearchController::class)->name('search');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::get('/profile/id-image', fn (Request $request) => \App\Support\SchoolIdImage::response($request->user()->id_image_path))->name('profile.id-image');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/name', [ProfileController::class, 'updateName'])->name('profile.name');
    Route::post('/profile/year-level', [ProfileController::class, 'updateYearLevel'])->name('profile.year-level');
    Route::get('/elections', function (Request $request) {
        $user = $request->user();

        if ($user->role === 'admin' || ($user->role === 'committee' && $user->canAccessPage('elections'))) {
            return app(ElectionController::class)->index();
        }

        if ($user->role === 'committee') {
            if ($user->canAccessPage('candidates')) {
                return redirect()->route('candidates');
            }

            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to access this page.');
        }

        return app(VoteController::class)->index($request);
    })->name('elections');
    Route::redirect('/vote', '/elections');
    Route::post('/elections/{election}/cast-vote', [VoteController::class, 'store'])->name('elections.cast-vote');
    Route::get('/ballot-submissions/{submission}/status', [VoteController::class, 'submissionStatus'])
        ->name('ballot-submissions.status');
    Route::get('/ballot-receipt/{receipt}', [BallotReceiptController::class, 'show'])->name('ballot-receipt.show');
    Route::post('/elections', [ElectionController::class, 'store'])->middleware('committee.page:elections')->name('elections.store');
    Route::put('/elections/{election}', [ElectionController::class, 'update'])->middleware('committee.page:elections')->name('elections.update');
    Route::delete('/elections/{election}', [ElectionController::class, 'destroy'])->middleware('committee.page:elections')->name('elections.destroy');
    Route::get('/candidates', [CandidateController::class, 'index'])->middleware('committee.page:candidates')->name('candidates');
    Route::get('/candidates/create', [CandidateController::class, 'create'])->middleware('committee.page:candidates')->name('candidates.create');
    Route::post('/candidates', [CandidateController::class, 'store'])->middleware('committee.page:candidates')->name('candidates.store');
    Route::get('/candidates/{candidate}/edit', [CandidateController::class, 'edit'])->middleware('committee.page:candidates')->name('candidates.edit');
    Route::put('/candidates/{candidate}', [CandidateController::class, 'update'])->middleware('committee.page:candidates')->name('candidates.update');
    Route::delete('/candidates/{candidate}', [CandidateController::class, 'destroy'])->middleware('committee.page:candidates')->name('candidates.destroy');
    Route::redirect('/committee', '/candidates')->middleware('committee.page:candidates')->name('committee');
    Route::post('/committee/candidates', [CommitteeController::class, 'store'])->middleware('committee.page:candidates')->name('committee.candidates.store');
    Route::get('/my-votes', [VoterPageController::class, 'myVotes'])->name('my-votes');
    Route::get('/results', [VoterPageController::class, 'results'])->name('results');
    Route::get('/announcements', [VoterPageController::class, 'announcements'])->name('announcements');
    Route::get('/announcements/manage', [AnnouncementController::class, 'index'])->middleware('committee.page:announcements')->name('announcements.manage');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->middleware('committee.page:announcements')->name('announcements.store');
    Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update'])->middleware('committee.page:announcements')->name('announcements.update');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->middleware('committee.page:announcements')->name('announcements.destroy');
    Route::get('/help', [SupportTicketController::class, 'index'])->name('help');
    Route::post('/help/tickets', [SupportTicketController::class, 'store'])->name('help.tickets.store');
    Route::get('/help/tickets/{ticket}', [SupportTicketController::class, 'show'])->name('help.tickets.show');
    Route::post('/help/tickets/{ticket}/messages', [SupportTicketController::class, 'storeMessage'])->name('help.tickets.messages.store');
    Route::get('/faq', [VoterPageController::class, 'faq'])->name('faq');
    Route::post('/faq/chat', [FaqChatController::class, 'store'])->middleware('throttle:15,1')->name('faq.chat');
    Route::get('/support', [AdminSupportController::class, 'index'])->middleware('committee.page:support')->name('support');
    Route::get('/support/tickets/{ticket}', [AdminSupportController::class, 'show'])->middleware('committee.page:support')->name('support.tickets.show');
    Route::post('/support/tickets/{ticket}/approve', [AdminSupportController::class, 'approve'])->middleware('committee.page:support')->name('support.tickets.approve');
    Route::post('/support/tickets/{ticket}/reject', [AdminSupportController::class, 'reject'])->middleware('committee.page:support')->name('support.tickets.reject');
    Route::post('/support/tickets/{ticket}/close', [AdminSupportController::class, 'close'])->middleware('committee.page:support')->name('support.tickets.close');
    Route::post('/support/tickets/{ticket}/messages', [AdminSupportController::class, 'storeMessage'])->middleware('committee.page:support')->name('support.tickets.messages.store');
    Route::get('/voters', [VoterController::class, 'index'])->middleware('committee.page:voters')->name('voters');
    Route::get('/voters/identical-names', [VoterController::class, 'identicalNames'])->middleware('committee.page:voters')->name('voters.identical-names');
    Route::post('/voters/{voter}/identical-name-flag', [VoterController::class, 'flagIdenticalName'])->middleware('committee.page:voters')->name('voters.identical-name-flag');
    Route::delete('/voters/{voter}/identical-name-flag', [VoterController::class, 'unflagIdenticalName'])->middleware('committee.page:voters')->name('voters.identical-name-unflag');
    Route::delete('/voters/bulk', [VoterController::class, 'destroyMany'])->middleware('committee.page:voters')->name('voters.destroy-many');
    Route::get('/voters/unlisted-registrations', function () {
        return \Inertia\Inertia::render('UnlistedRegistrations', [
            'attempts' => \Illuminate\Support\Facades\DB::table('unlisted_registrations')
                ->latest('id')->paginate(25)->through(function ($attempt) {
                    $attempt->has_id_image = isset($attempt->id_image_path);
                    unset($attempt->id_image_path);

                    return $attempt;
                }),
        ]);
    })->middleware('admin')->name('voters.unlisted-registrations');
    Route::delete('/voters/unlisted-registrations/bulk', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:1000'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $ids = array_values(array_unique($validated['ids']));
        $disk = \Illuminate\Support\Facades\Storage::disk('local');

        \Illuminate\Support\Facades\DB::transaction(function () use ($ids, $disk) {
            $records = \Illuminate\Support\Facades\DB::table('unlisted_registrations')
                ->whereIn('id', $ids)->lockForUpdate()->get();
            abort_unless($records->count() === count($ids), 422, 'One or more selected attempts no longer exist. Refresh the page and try again.');

            foreach ($records as $record) {
                if ($record->id_image_path) {
                    abort_unless($disk->delete($record->id_image_path), 500);
                }
            }

            \Illuminate\Support\Facades\DB::table('unlisted_registrations')->whereIn('id', $ids)->delete();
        });

        $count = count($ids);

        return redirect()->route('voters.unlisted-registrations')->with('success', $count === 1
            ? '1 registration attempt has been deleted.'
            : "{$count} registration attempts have been deleted.");
    })->middleware('admin')->name('voters.unlisted-registrations.destroy-many');
    Route::get('/voters/unlisted-registrations/{attempt}/id-image', function (string $attempt) {
        $path = \Illuminate\Support\Facades\DB::table('unlisted_registrations')->where('id', $attempt)->value('id_image_path');
        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        abort_unless($path && $disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    })->whereNumber('attempt')->middleware('admin')->name('voters.unlisted-registrations.id-image');
    Route::delete('/voters/unlisted-registrations/{attempt}', function (string $attempt) {
        \Illuminate\Support\Facades\DB::transaction(function () use ($attempt) {
            $query = \Illuminate\Support\Facades\DB::table('unlisted_registrations')->where('id', $attempt);
            $record = (clone $query)->lockForUpdate()->first();
            if ($record?->id_image_path) {
                abort_unless(\Illuminate\Support\Facades\Storage::disk('local')->delete($record->id_image_path), 500);
            }
            $query->delete();
        });

        return redirect()->route('voters.unlisted-registrations');
    })->whereNumber('attempt')->middleware('admin')->name('voters.unlisted-registrations.destroy');
    Route::get('/voters/{voter}', [VoterController::class, 'show'])->middleware('committee.page:voters')->name('voters.show');
    Route::get('/voters/{voter}/id-image', [VoterController::class, 'idImage'])->middleware('committee.page:voters')->name('voters.id-image');
    Route::get('/voters/{voter}/exists', [VoterController::class, 'exists'])->middleware('committee.page:voters')->name('voters.exists');
    Route::post('/voters/{voter}/verify', [VoterController::class, 'verify'])->middleware('committee.page:voters')->name('voters.verify');
    Route::post('/voters/{voter}/reject', [VoterController::class, 'reject'])->middleware('committee.page:voters')->name('voters.reject');
    Route::post('/voters/{voter}/rerun-ocr', [VoterController::class, 'rerunOcr'])->middleware('committee.page:voters')->name('voters.rerun-ocr');
    Route::delete('/voters/{voter}', [VoterController::class, 'destroy'])->middleware('committee.page:voters')->name('voters.destroy');
    Route::get('/reactivation-requests', [AdminReactivationController::class, 'index'])->middleware('committee.page:reactivation')->name('reactivation-requests');
    Route::post('/reactivation-requests/{reactivationRequest}/process', [AdminReactivationController::class, 'process'])
        ->middleware('committee.page:reactivation')
        ->name('reactivation-requests.process');
    Route::get('/monitoring', [MonitoringController::class, 'index'])->middleware('committee.page:monitoring')->name('monitoring');
    Route::get('/reports', [ReportController::class, 'index'])->middleware('committee.page:reports')->name('reports');
    Route::get('/reports/{election}/export/{type}/availability', [ReportController::class, 'exportAvailability'])
        ->middleware('committee.page:reports')
        ->name('reports.export.availability');
    Route::get('/reports/{election}/export/{type}/pdf', [ReportController::class, 'exportPdf'])
        ->middleware('committee.page:reports')
        ->name('reports.export.pdf');
    Route::get('/reports/{election}/export/{type}/excel', [ReportController::class, 'exportExcel'])
        ->middleware('committee.page:reports')
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
    Route::resource('settings/sections', \App\Http\Controllers\SectionController::class)->only(['store', 'update', 'destroy'])->middleware('admin');
    Route::post('/settings/partylists', [SettingsController::class, 'storePartylist'])->middleware('admin')->name('settings.partylists.store');
    Route::put('/settings/partylists/{partylist}', [SettingsController::class, 'updatePartylist'])->middleware('admin')->name('settings.partylists.update');
    Route::delete('/settings/partylists/{partylist}', [SettingsController::class, 'destroyPartylist'])->middleware('admin')->name('settings.partylists.destroy');
    Route::put('/settings/dts-registration', [SettingsController::class, 'updateDtsRegistration'])->middleware('admin')->name('settings.dts-registration.update');
    Route::put('/settings/ua-management', [SettingsController::class, 'updateUaManagement'])->middleware('admin')->name('settings.ua-management.update');
    Route::put('/settings/school-year', [SettingsController::class, 'updateSchoolYear'])->middleware('admin')->name('settings.school-year.update');
    Route::get('/disabled-accounts', [AdminDisabledAccountController::class, 'index'])->middleware('admin')->name('disabled-accounts');
    Route::get('/disabled-accounts/{user}', [AdminDisabledAccountController::class, 'show'])->middleware('admin')->name('disabled-accounts.show');
    Route::post('/disabled-accounts/{user}/process', [AdminDisabledAccountController::class, 'process'])->middleware('admin')->name('disabled-accounts.process');
    Route::post('/settings/ssc-members', [SettingsController::class, 'storeSscMembers'])->middleware('admin')->name('settings.ssc-members.store');
    Route::delete('/settings/ssc-members', [SettingsController::class, 'destroyAllSscMembers'])->middleware('admin')->name('settings.ssc-members.destroy-all');
    Route::delete('/settings/ssc-members/{sscMemberImage}', [SettingsController::class, 'destroySscMember'])->middleware('admin')->name('settings.ssc-members.destroy');
    Route::post('/settings/gallery', [SettingsController::class, 'storeGalleryImages'])->middleware('admin')->name('settings.gallery.store');
    Route::put('/settings/gallery/style', [SettingsController::class, 'updateGalleryStyle'])->middleware('admin')->name('settings.gallery.style.update');
    Route::delete('/settings/gallery', [SettingsController::class, 'destroyAllGalleryImages'])->middleware('admin')->name('settings.gallery.destroy-all');
    Route::delete('/settings/gallery/{galleryImage}', [SettingsController::class, 'destroyGalleryImage'])->middleware('admin')->name('settings.gallery.destroy');
    Route::get('/accounts', [AccountController::class, 'index'])->middleware('admin')->name('accounts');
    Route::post('/accounts', [AccountController::class, 'store'])->middleware('admin')->name('accounts.store');
    Route::post('/accounts/committee', [AccountController::class, 'storeCommittee'])->middleware('admin')->name('accounts.committee.store');
    Route::put('/accounts/{user}', [AccountController::class, 'update'])->middleware('admin')->name('accounts.update');
    Route::delete('/accounts/{user}', [AccountController::class, 'destroy'])->middleware('admin')->name('accounts.destroy');
    Route::delete('/accounts/committee/{user}', [AccountController::class, 'destroyCommittee'])->middleware('admin')->name('accounts.committee.destroy');
    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('admin')->name('permissions');
    Route::put('/permissions/{user}', [PermissionController::class, 'update'])->middleware('admin')->name('permissions.update');
    Route::controller(\App\Http\Controllers\OfficialStudentRecordController::class)->middleware('admin')->group(function () {
        Route::get('/official-student-records', 'index')->name('official-student-records');
        Route::get('/official-student-records/export', 'export')->name('official-student-records.export');
        Route::post('/official-student-records', 'store')->name('official-student-records.store');
        Route::delete('/official-student-records/{record}', 'destroy')->whereNumber('record')->name('official-student-records.destroy');
        Route::delete('/official-student-records/bulk', 'destroyMany')->name('official-student-records.destroy-many');
        Route::patch('/official-student-records/{record}/email', 'updateEmail')->whereNumber('record')->name('official-student-records.email.update');
        Route::post('/official-student-records/confirm', 'confirm')->name('official-student-records.confirm');
        Route::get('/removed-student-records', 'index')->name('removed-student-records');
        Route::get('/removed-student-records/export', 'export')->name('removed-student-records.export');
        Route::post('/removed-student-records/{record}/restore', 'restore')->whereNumber('record')->name('removed-student-records.restore');
        Route::post('/official-student-records/duplicate-rows/{row}/review', 'reviewDuplicate')->whereNumber('row')->name('official-student-records.duplicates.review');
        Route::get('/official-student-records/import-rows', 'importRows')->name('official-student-records.import-rows');
    });

    Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('admin')->name('audit-logs');
    Route::get('/registration-attempts', [RegistrationAttemptController::class, 'index'])->middleware('admin')->name('registration-attempts');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
