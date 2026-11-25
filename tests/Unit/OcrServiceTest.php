<?php

use App\Services\OcrService;

function ocrExtract(string $text): array
{
    $service = new OcrService();
    $method  = (new ReflectionClass(OcrService::class))->getMethod('extractName');
    $method->setAccessible(true);
    $extractName = fn (string $t) => $method->invoke($service, $t);

    $method = (new ReflectionClass(OcrService::class))->getMethod('extractStudentId');
    $method->setAccessible(true);
    $extractStudentId = fn (string $t) => $method->invoke($service, $t);

    $method = (new ReflectionClass(OcrService::class))->getMethod('extractCourse');
    $method->setAccessible(true);
    $extractCourse = fn (string $t) => $method->invoke($service, $t);

    return [
        'name'      => $extractName($text),
        'studentId' => $extractStudentId($text),
        'course'    => $extractCourse($text),
    ];
}

test('extracts fields from BCC vertical ID layout', function () {
    $text = <<<'TEXT'
BAAO COMMUNITY COLLEGE
Baao, Camarines Sur
ANGEL BRIGETH M. SERDAN
Name
BACHELOR OF SCIENCE IN INFORMATION SYSTEMS
Course
In case of emergency, please contact:
BONIFACIO SERDAN
TARUBOG KILANTAAO, SAGÑAY, CAMARINES SUR
Contact No.: 09990038319
23-1-0516
Student No.
TEXT;

    $result = ocrExtract($text);

    expect($result['name'])->toBe('ANGEL BRIGETH M. SERDAN');
    expect($result['studentId'])->toBe('23-1-0516');
    expect($result['course'])->toBe('BACHELOR OF SCIENCE IN INFORMATION SYSTEMS');
});

test('extracts fields from BCC horizontal ID layout', function () {
    $text = <<<'TEXT'
BAAO COMMUNITY COLLEGE
SAN JUAN, BAAO, CAMARINES SUR
Student ID Card
Student No: 24-1-0462
Signature of Student
RELOS, ASHLEY KURTH R.
Ombao Heights, Bula, Camarines Sur
Bachelor of Secondary Education
Social Studies
09947834741
TEXT;

    $result = ocrExtract($text);

    expect($result['name'])->toBe('RELOS, ASHLEY KURTH R.');
    expect($result['studentId'])->toBe('24-1-0462');
    expect($result['course'])->toBe('Bachelor of Secondary Education - Social Studies');
});

test('extracts fields from noisy OCR output for BCC vertical ID', function () {
    $text = <<<'TEXT'
BAAO COMMUNITY COLLEGE
Baao, Camarines Sur
ANGEL BRIGETH M. SERDAN
Name
BACHELOR OF SCIENCE IN INFORMATION SYSTEMS
Course
In case of emergency, please contact:
BONIFACIO SERDAN
23 1 0516
Student No.
TEXT;

    $result = ocrExtract($text);

    expect($result['name'])->toBe('ANGEL BRIGETH M. SERDAN');
    expect($result['studentId'])->toBe('23-1-0516');
    expect($result['course'])->toBe('BACHELOR OF SCIENCE IN INFORMATION SYSTEMS');
});

test('does not use emergency contact as student name on vertical BCC ID', function () {
    $text = <<<'TEXT'
BAAO COMMUNITY COLLEGE
Name
Course
In case of emergency, please contact:
BONIFACIO SERDAN
23-1-0516
Student No.
TEXT;

    $result = ocrExtract($text);

    expect($result['name'])->toBeNull();
    expect($result['studentId'])->toBe('23-1-0516');
});

test('extracts student id from horizontal BCC label variants', function () {
    $text = <<<'TEXT'
Student No. 24-1-0462
RELOS, ASHLEY KURTH R.
Bachelor of Secondary Education
TEXT;

    $result = ocrExtract($text);

    expect($result['studentId'])->toBe('24-1-0462');
    expect($result['name'])->toBe('RELOS, ASHLEY KURTH R.');
});

test('extracts the full enrollment-form name row using OCR word coordinates across large gaps', function () {
    $service = new OcrService();
    $method = new ReflectionClass($service)->getMethod('extractNameFromOverlay');
    $method->setAccessible(true);
    $word = fn (string $text, int $left, int $top, int $width = 70, int $height = 18) => [
        'WordText' => $text,
        'Left' => $left,
        'Top' => $top,
        'Width' => $width,
        'Height' => $height,
    ];
    $overlay = [
        ['Words' => [
            $word('Student', 40, 80, 60), $word('Number:', 104, 80, 78), $word('23-1-0326', 190, 80, 90),
        ]],
        ['Words' => [
            $word('Name:', 40, 108, 58),
            $word('De', 320, 108, 24), $word('la', 347, 108, 16), $word('Cruz', 366, 108, 44),
            $word('Anne-Marie', 760, 108, 112), $word("O'Neil", 1260, 108, 70), $word('Jr.', 1340, 108, 27),
        ]],
        ['Words' => [
            $word('(Last', 300, 132, 42), $word('Name)', 345, 132, 49),
            $word('(First', 760, 132, 42), $word('Name)', 805, 132, 49),
            $word('(Middle', 1240, 132, 56), $word('Name)', 1299, 132, 49),
        ]],
        ['Words' => [
            $word('Year', 40, 162, 42), $word('&', 84, 162, 12), $word('Course:', 100, 162, 68),
            $word('BSIS-4A', 174, 162, 82),
        ]],
        ['Words' => [
            $word('Dean,', 50, 500, 55), $word('College', 110, 500, 72), $word('Registrar', 190, 500, 88),
        ]],
    ];

    expect($method->invoke($service, $overlay, true))->toBe("De la Cruz Anne-Marie O'Neil Jr.");
});

test('strict enrollment-form scan will not guess a name when the three name captions are missing', function () {
    $service = new OcrService();
    $method = new ReflectionClass($service)->getMethod('extractNameFromOverlay');
    $method->setAccessible(true);
    $word = fn (string $text, int $left, int $top, int $width = 70) => [
        'WordText' => $text,
        'Left' => $left,
        'Top' => $top,
        'Width' => $width,
        'Height' => 18,
    ];
    $overlay = [['Words' => [
        $word('Name:', 30, 120, 58), $word('Student', 130, 120, 68),
        $word('Name', 205, 120, 52), $word('Dean', 50, 700),
    ]]];

    expect($method->invoke($service, $overlay, true))->toBeNull();
});

test('keeps the complete name when OCR coordinates show a tilted row', function () {
    $service = new OcrService();
    $method = new ReflectionClass($service)->getMethod('extractNameFromOverlay');
    $method->setAccessible(true);
    $word = fn (string $text, int $left, int $top, int $width = 70) => [
        'WordText' => $text,
        'Left' => $left,
        'Top' => $top,
        'Width' => $width,
        'Height' => 18,
    ];
    $overlay = [
        ['Words' => [$word('Name:', 30, 120, 58)]],
        ['Words' => [$word('Santos', 230, 126, 75)]],
        ['Words' => [$word('Maria', 780, 138, 60)]],
        ['Words' => [$word('Clara', 1260, 151, 58)]],
        ['Words' => [$word('(Last', 225, 151, 40), $word('Name)', 267, 151, 48)]],
        ['Words' => [$word('(First', 775, 163, 40), $word('Name)', 817, 163, 48)]],
        ['Words' => [$word('(Middle', 1255, 176, 54), $word('Name)', 1311, 176, 48)]],
        ['Words' => [$word('Dean', 30, 700), $word('Jocelyn', 90, 700), $word('Mberina', 175, 700)]],
    ];

    expect($method->invoke($service, $overlay))->toBe('Santos Maria Clara');
});

test('reads each name component above its matching caption and excludes Student Number text', function () {
    $service = new OcrService();
    $method = new ReflectionClass($service)->getMethod('extractNameFromOverlay');
    $method->setAccessible(true);
    $word = fn (string $text, int $left, int $top, int $width = 60, int $height = 14) => [
        'WordText' => $text,
        'Left' => $left,
        'Top' => $top,
        'Width' => $width,
        'Height' => $height,
    ];
    $overlay = [
        ['Words' => [$word('Student', 20, 8), $word('Number:', 81, 8, 65), $word('23-1-0516', 150, 8, 75)]],
        ['Words' => [$word('Name:', 20, 31, 48), $word('SERDAN', 145, 31, 70), $word('ANGEL', 500, 31), $word('BRIGETH', 563, 31, 75), $word('MONREAL', 925, 31, 75)]],
        ['Words' => [$word('(Last', 140, 50, 35), $word('Name)', 177, 50, 40)]],
        ['Words' => [$word('(First', 497, 50, 35), $word('Name)', 534, 50, 40)]],
        ['Words' => [$word('(Middle', 922, 50, 50), $word('Name)', 974, 50, 40)]],
        ['Words' => [$word('Year', 20, 70), $word('&', 64, 70, 10), $word('Course:', 76, 70), $word('BSIS-4A', 145, 70, 70)]],
    ];

    expect($method->invoke($service, $overlay))->toBe('SERDAN ANGEL BRIGETH MONREAL');
});

test('removes Number when OCR merges it directly into the surname', function () {
    $service = new OcrService();
    $method = new ReflectionClass($service)->getMethod('cleanName');
    $method->setAccessible(true);

    expect($method->invoke($service, 'NumberSERDAN ANGEL BRIGETH MONREAL'))
        ->toBe('SERDAN ANGEL BRIGETH MONREAL');
});

test('rejects caption fragments as an enrollment-form full name so OCR can retry', function () {
    $service = new OcrService();
    $method = new ReflectionClass($service)->getMethod('validateEnrollmentName');
    $method->setAccessible(true);

    $result = $method->invoke($service, new \App\Services\OcrResult(true, 'SE Last Na', '23-1-0516', 'BSIS'));

    expect($result->name)->toBeNull()
        ->and($result->studentId)->toBe('23-1-0516');
});

test('extracts year, course, and section dynamically from the Year and Course field', function () {
    $service = new OcrService();
    $method = new ReflectionClass($service)->getMethod('extractYearCourse');
    $method->setAccessible(true);

    expect($method->invoke($service, 'BAAO COMMUNITY COLLEGE' . "\n" . 'Year & Course: BSIS-4A'))
        ->toMatchArray([
            'course' => 'BSIS',
            'year_level' => '4th Year',
            'section' => 'A',
            'year_course' => 'BSIS-4A',
        ]);
});

test('does not fall back to dean or registrar names when enrollment-form student name is unreadable', function () {
    $service = new OcrService();
    $method = new ReflectionClass($service)->getMethod('extractName');
    $method->setAccessible(true);
    $text = <<<'TEXT'
BAAO COMMUNITY COLLEGE ENROLLMENT FORM
Student Number: 23-1-0326
Name: (Last Name) (First Name) (Middle Name)
Year & Course: BSIS-4A
JOCELYN MBERINA Dean, College of Information Technology
ISIDRO A. BUERE College Registrar
TEXT;

    expect($method->invoke($service, $text))->toBeNull();
});
