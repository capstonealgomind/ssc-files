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
