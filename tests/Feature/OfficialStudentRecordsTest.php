<?php

use App\Models\OfficialStudentRecord;
use App\Models\User;
use App\Services\OfficialStudentImport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    foreach (glob(database_path('migrations/*.php')) as $migration) {
        // This legacy MySQL-only migration changes a default irrelevant to these tests.
        if (! str_contains($migration, 'fix_registration_attempts_created_at_default')) {
            (require $migration)->up();
        }
    }
    $department = \App\Models\Department::create(['name' => 'Computing']);
    $course = \App\Models\Course::create(['name' => 'BSIT', 'department_id' => $department->id, 'duration_years' => 4]);
    $year = \App\Models\YearLevel::create(['name' => 'First Year', 'sort_order' => 1]);
    \App\Models\Section::create(['course_id' => $course->id, 'year_level_id' => $year->id, 'letter' => 'A']);
});

function officialWorkbook(array $rows, string $extension): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'official-students');
    $book = new Spreadsheet;
    $book->getActiveSheet()->fromArray([array_values(OfficialStudentRecord::HEADERS), ...$rows]);
    $writer = $extension === 'xlsx' ? new Xlsx($book) : new Xls($book);
    $writer->save($path);

    return new UploadedFile($path, 'official.'.$extension, null, UPLOAD_ERR_OK, true);
}

function officialImportResult(UploadedFile $file): array
{
    [$rows, $summary] = app(OfficialStudentImport::class)->read($file);
    $id = app(\App\Services\OfficialStudentSync::class)->apply($rows, $summary, false, $file->getClientOriginalName(), 1);

    return json_decode(DB::table('official_student_imports')->where('id', $id)->value('result'), true);
}

test('skips duplicate Excel rows, rejects invalid uploads and updates matching IDs', function (string $extension) {
    $first = ['00123', 'Alex Reyes', 'Computing', 'BSIT', 'First Year', 'A', 'Enrolled'];
    $other = ['00124', 'Sam Cruz', 'Computing', 'BSIT', 'First Year', 'A', 'Eligible'];
    $changed = array_replace($first, [1 => 'Updated Name']);

    foreach ([
        [[$first, $first, [], array_replace($other, [0 => 'INVALID']), $other], ['added' => 2, 'duplicates' => 1, 'invalid' => 1]],
        [[$first, $first, $other], ['processed' => 3, 'added' => 0, 'duplicates' => 3, 'updated' => 0]],
        [[$first, $other], ['added' => 0, 'duplicates' => 2]],
        [[$changed], ['added' => 0, 'updated' => 1, 'retained' => 0, 'removed' => 0]],
    ] as [$rows, $expected]) {
        $file = officialWorkbook($rows, $extension);
        try {
            expect(officialImportResult($file))->toMatchArray($expected);
        } finally {
            unlink($file->getPathname());
        }
    }

    $this->assertDatabaseHas('official_student_records', ['student_id' => '00123', 'full_name' => 'Updated Name']);
    $this->assertDatabaseCount('official_student_records', 2);
    $this->assertDatabaseCount('users', 0);
})->with(['xlsx', 'xls']);

test('full sync previews removals, rejects stale confirmation and restores with acknowledgement', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $headers = implode(',', OfficialStudentRecord::HEADERS);
    $first = "00001,Alex Reyes,Computing,BSIT,First Year,A,Eligible";
    $other = "00002,Sam Cruz,Computing,BSIT,First Year,A,Eligible";
    $new = "00003,Pat Cruz,Computing,BSIT,First Year,A,Eligible";
    $upload = fn ($rows) => UploadedFile::fake()->createWithContent('students.csv', $headers."\n".$rows."\n");
    $this->actingAs($admin);
    $this->post(route('official-student-records.store'), ['file' => $upload($first."\n".$other), 'mode' => 'add_update'])->assertRedirect();
    $this->post(route('official-student-records.store'), ['file' => $upload($first."\n".$new), 'mode' => 'full'])->assertRedirect();
    $preview = session('official_sync_preview');
    expect($preview['counts'])->toMatchArray(['added' => 1, 'duplicates' => 1, 'removed' => 1]);
    $this->assertDatabaseCount('official_student_records', 2);

    $this->post(route('official-student-records.store'), ['file' => $upload($first), 'mode' => 'add_update'])->assertRedirect();
    $this->post(route('official-student-records.confirm'), ['token' => $preview['token'], 'confirmed' => true])->assertSessionHasErrors('file');
    $this->post(route('official-student-records.store'), ['file' => $upload($first."\n".$new), 'mode' => 'full'])->assertRedirect();
    $this->post(route('official-student-records.confirm'), ['token' => session('official_sync_preview.token'), 'confirmed' => true])->assertRedirect();
    expect(OfficialStudentRecord::whereNotNull('removed_at')->pluck('student_id')->all())->toBe(['00002']);
    $this->get(route('removed-student-records'))->assertInertia(fn ($page) => $page
        ->component('OfficialStudentRecords')->where('removed', true)->where('records.data.0.student_id', '00002'));
    $restore = route('removed-student-records.restore', OfficialStudentRecord::where('student_id', '00002')->value('id'));
    $this->post($restore, ['confirmed' => true])->assertSessionHasErrors('restore');
    $this->post($restore, ['confirmed' => true, 'acknowledged' => true])->assertRedirect();
    $this->assertDatabaseHas('official_student_records', ['student_id' => '00002', 'removed_at' => null]);
    $this->assertDatabaseHas('official_student_imports', ['filename' => 'Restore 00002']);
    $this->assertDatabaseCount('users', 1);
});

test('imports CSV and reports missing columns', function () {
    $headers = implode(',', OfficialStudentRecord::HEADERS);
    $file = UploadedFile::fake()->createWithContent('students.csv', $headers."\n00001,Alex Reyes,Computing,BSIT,First Year,A,Eligible\n");
    expect(officialImportResult($file))->toMatchArray(['added' => 1]);
    $this->assertDatabaseHas('official_student_records', ['student_id' => '00001']);

    $file = UploadedFile::fake()->createWithContent('students.csv', "Student ID,Full Name\n00002,Alex Reyes\n");
    expect(officialImportResult($file)['error'])->toContain('Missing or repeated columns');
});

test('administrator upload redirects to the refreshed records and voters are forbidden', function () {
    $voter = User::factory()->create(['role' => 'voter']);
    $this->actingAs($voter)->get(route('official-student-records'))->assertRedirect(route('dashboard'));
    $this->post(route('official-student-records.store'))->assertRedirect(route('dashboard'));

    $admin = User::factory()->create(['role' => 'admin']);
    $headers = implode(',', OfficialStudentRecord::HEADERS);
    $file = UploadedFile::fake()->createWithContent('students.csv', $headers."\n00001,Alex Reyes,Computing,BSIT,First Year,A,Eligible\n");
    $this->actingAs($admin)->post(route('official-student-records.store'), ['file' => $file, 'mode' => 'add_update'])
        ->assertRedirect()
        ->assertSessionHas('official_student_import_id');
    $this->get(route('official-student-records'))
        ->assertInertia(fn ($page) => $page->component('OfficialStudentRecords')
            ->where('records.data.0.student_id', '00001')->where('summary.added', 1));
    $this->from(route('official-student-records'))->post(route('official-student-records.store'), [
        'file' => UploadedFile::fake()->createWithContent('students.txt', $headers),
    ])->assertSessionHasErrors('file');
});


test('returning home clears the registration scan and requires a new scan', function () {
    $disk = \Illuminate\Support\Facades\Storage::fake('local');
    $path = 'registration-ids/test.png';
    $disk->put($path, 'test image');

    $this->withSession([
        'registration_scan' => ['path' => $path, 'expires' => now()->addHour()->timestamp],
        'registration_pending' => ['attempts' => 0],
    ])->post(route('register.reset'))->assertRedirect(route('home'))
        ->assertSessionMissing('registration_scan')
        ->assertSessionMissing('registration_pending');

    $disk->assertMissing($path);
    $this->get(route('register'))->assertRedirect(route('register.id-scan'));
});

test('accepts enrollment-form OCR name mismatches during upload for later official verification', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    OfficialStudentRecord::insert([
        'student_id' => '00123', 'full_name' => 'Alex Reyes', 'department' => 'Computing',
        'course' => 'BSIT', 'year_level' => 'First Year', 'section' => 'A', 'status' => 'Enrolled',
    ]);

    $ocr = Mockery::mock(\App\Services\OcrService::class);
    $ocr->shouldReceive('extract')->once()->andReturn(new \App\Services\OcrResult(true, 'Different OCR Name', '00123', 'BSIT'));
    $this->instance(\App\Services\OcrService::class, $ocr);

    $this->post(route('register.id-scan.store'), [
        'image' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jO9sAAAAASUVORK5CYII=',
        'image_quality' => 'good',
        'document_type' => 'enrollment_form',
    ])->assertRedirect(route('register.details'))
        ->assertSessionHas('registration_scan.name', 'Different OCR Name')
        ->assertSessionHas('registration_scan.student_id', '00123');
});

test('reserves sequential short voter IDs after existing IDs', function () {
    $year = now()->year;
    User::factory()->create(['voter_id_number' => "VID-{$year}-00227"]);
    User::factory()->create(['voter_id_number' => "VID-{$year}-9ABCDEF012345678"]);

    expect(User::generateVoterIdNumber())->toBe("VID-{$year}-00228");
    expect(User::generateVoterIdNumber())->toBe("VID-{$year}-00229");
});

test('registration saves a voter only after OTP, official record matching and fraud checks', function (string $officialStatus, bool $approved) {
    \Illuminate\Support\Facades\Storage::fake('local');
    \Illuminate\Support\Facades\Storage::fake('public');
    \Illuminate\Support\Facades\Mail::fake();

    $department = \App\Models\Department::where('name', 'Computing')->firstOrFail();
    $course = \App\Models\Course::where('name', 'BSIT')->firstOrFail();
    $year = \App\Models\YearLevel::where('name', 'First Year')->firstOrFail();
    if ($officialStatus !== 'Missing') {
        OfficialStudentRecord::insert([
            'student_id' => '00123', 'full_name' => 'Alex Reyes', 'department' => 'Computing',
            'course' => 'BSIT', 'year_level' => 'First Year', 'section' => $officialStatus === 'Conflict' ? 'B' : 'A', 'status' => $officialStatus === 'Conflict' ? 'Eligible' : $officialStatus,
        ]);
    }

    $details = [
        'name' => 'Alex Reyes', 'email' => 'alex.reyes@example.com', 'student_id_number' => '00123',
        'department_id' => $department->id, 'course_id' => $course->id, 'year_level_id' => $year->id,
        'section' => 'A', 'password' => 'SecurePass123!', 'password_confirmation' => 'SecurePass123!',
    ];

    $this->get(route('register'))->assertRedirect(route('register.id-scan'));
    $this->post(route('register.store'), $details)->assertRedirect(route('register.id-scan'));
    $this->assertDatabaseCount('users', 0);

    $ocr = Mockery::mock(\App\Services\OcrService::class);
    $ocr->shouldReceive('extract')->once()->andReturnUsing(
        fn () => new \App\Services\OcrResult(true, 'Alex Reyes', '00123', 'BSIT'),
    );
    $this->instance(\App\Services\OcrService::class, $ocr);
    $this->post(route('register.id-scan.store'), [
        'image' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jO9sAAAAASUVORK5CYII=',
        'image_quality' => 'good',
    ])->assertRedirect(route('register.details'));

    $this->get(route('register.details'))->assertInertia(fn ($page) => $page
        ->component('Auth/Register')
        ->where('scannedDetails.name', 'Alex Reyes')
        ->where('scannedDetails.student_id', '00123')
        ->where('scannedDetails.course', 'BSIT')
        ->where('scannedDetails.department_id', $details['department_id'])
        ->where('scannedDetails.course_id', $details['course_id'])
        ->missing('scannedDetails.path'));

    $this->post(route('register.store'), $details)->assertRedirect(route('register.verify-otp'));
    $this->assertDatabaseCount('users', 0);

    $mail = \Illuminate\Support\Facades\Mail::sent(\App\Mail\VerifyEmail::class)->sole();
    $mail->assertSeeInText($mail->voterIdNumber);
    $mail->assertSeeInHtml($mail->voterIdNumber);
    $mail->assertSeeInHtml($mail->otp);
    expect($mail->voterIdNumber)->toMatch('/^VID-\d{4}-\d{5}$/');
    $this->from(route('register.verify-otp'))
        ->post(route('register.verify-otp.store'), ['otp' => $mail->otp === '000000' ? '000001' : '000000'])
        ->assertSessionHasErrors('otp');
    $this->assertDatabaseCount('users', 0);

    $this->post(route('register.verify-otp.store'), ['otp' => $mail->otp])
        ->assertRedirect(route('register.decision'));
    $this->get(route('register.decision'))
        ->assertInertia(fn ($page) => $page->component('Auth/EmailVerificationResult')->where('success', $approved));
    $this->assertDatabaseCount('users', $approved ? 1 : 0);
    if ($officialStatus === 'Missing') {
        $this->assertDatabaseHas('unlisted_registrations', [
            'student_id' => '00123', 'email' => $details['email'],
            'department' => 'Computing', 'course' => 'BSIT', 'year_level' => 'First Year', 'section' => 'A',
        ]);
        $attempt = \Illuminate\Support\Facades\DB::table('unlisted_registrations')->sole();
        expect($attempt->id_image_path)->toStartWith('unlisted-registration-ids/');
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($attempt->id_image_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($attempt->id_image_path);
        $reviewUrl = route('voters.unlisted-registrations.id-image', $attempt->id);
        $this->get($reviewUrl)->assertRedirect();
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get($reviewUrl)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->delete(route('voters.unlisted-registrations.destroy', $attempt->id))->assertRedirect();
        $this->assertDatabaseMissing('unlisted_registrations', ['id' => $attempt->id]);
        \Illuminate\Support\Facades\Storage::disk('local')->assertMissing($attempt->id_image_path);
    } else {
        $this->assertDatabaseCount('unlisted_registrations', 0);
    }
    if ($approved) {
        $this->assertDatabaseHas('users', ['student_id_number' => '00123', 'verification_status' => 'approved', 'voter_id_number' => $mail->voterIdNumber]);
    }
})->with([
    'eligible' => ['Eligible', true],
    'for verification' => ['For Verification', true],
    'missing' => ['Missing', false],
    'conflicting section' => ['Conflict', false],
]);

test('all summary categories retrieve matching snapshots and the latest batch survives refresh', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);
    $headers = implode(',', OfficialStudentRecord::HEADERS);
    $row = fn ($id, $name) => "$id,$name,Computing,BSIT,First Year,A,Eligible";
    $upload = fn ($rows) => UploadedFile::fake()->createWithContent('students.csv', $headers."\n".implode("\n", $rows));
    $store = route('official-student-records.store');
    $this->post($store, ['mode' => 'add_update', 'file' => $upload([$row('001', 'Alex Reyes'), $row('002', 'Sam Cruz'), $row('004', 'Pat Cruz')])])->assertRedirect();
    $this->post($store, ['mode' => 'add_update', 'file' => $upload([$row('001', 'Alex Ramos'), $row('002', 'Sam Cruz'), $row('003', 'Jo Cruz'), $row('003', 'Jo Cruz'), $row('005', '')])])->assertRedirect();
    $batch = session('official_student_import_id');
    foreach (['processed' => 5, 'added' => 1, 'updated' => 1, 'retained' => 0, 'duplicates' => 2, 'invalid' => 1, 'removed' => 0] as $category => $count) {
        $this->getJson(route('official-student-records.import-rows', ['batch' => $batch, 'category' => $category === 'processed' ? null : $category]))
            ->assertOk()->assertJsonPath('total', $count);
    }
    $this->get(route('official-student-records'));
    $this->get(route('official-student-records'))->assertInertia(fn ($page) => $page->where('batch', $batch)->where('summary.processed', 5));

    $this->post($store, ['mode' => 'full', 'file' => $upload([$row('001', 'Alex Ramos'), $row('002', 'Sam Cruz'), $row('003', 'Jo Cruz')])])->assertRedirect();
    $this->post(route('official-student-records.confirm'), ['token' => session('official_sync_preview.token'), 'confirmed' => true])->assertRedirect();
    $this->getJson(route('official-student-records.import-rows', ['batch' => session('official_student_import_id'), 'category' => 'removed']))
        ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.details.values.student_id', '004')
        ->assertJsonStructure(['data' => [['details' => ['removed_at', 'message']]]]);
});

test('full synchronization counts the 90 remaining students as duplicates and removes the 10 absent students', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);
    $headers = implode(',', OfficialStudentRecord::HEADERS);
    $upload = fn ($count) => UploadedFile::fake()->createWithContent('students.csv', $headers."\n".implode("\n",
        array_map(fn ($id) => "$id,Student $id,Computing,BSIT,First Year,A,Eligible", range(1, $count))));
    $store = route('official-student-records.store');

    $this->post($store, ['mode' => 'add_update', 'file' => $upload(100)])->assertRedirect();
    $this->post($store, ['mode' => 'full', 'file' => $upload(90)])->assertRedirect();
    $preview = session('official_sync_preview');
    expect($preview['counts'])->toMatchArray(['processed' => 90, 'duplicates' => 90, 'removed' => 10, 'added' => 0, 'updated' => 0]);
    $this->post(route('official-student-records.confirm'), ['token' => $preview['token'], 'confirmed' => true])->assertRedirect();

    $batch = session('official_student_import_id');
    foreach (['duplicates' => 90, 'removed' => 10] as $category => $count) {
        $this->getJson(route('official-student-records.import-rows', compact('batch', 'category')))
            ->assertOk()->assertJsonPath('total', $count);
    }
    expect(OfficialStudentRecord::whereNull('removed_at')->count())->toBe(90);
    $this->assertDatabaseCount('official_student_records', 100);
});
