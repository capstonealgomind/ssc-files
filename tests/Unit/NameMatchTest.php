<?php

use App\Services\FraudDetectionService;

test('ocr last-first all caps matches typed first-last name', function () {
    expect(FraudDetectionService::namesMatch(
        'VARGAS, JHAMELLIE ANNE P.',
        'Jhamellie Anne P. Vargas',
    ))->toBeTrue();
});

test('ocr first-last all caps matches typed name', function () {
    expect(FraudDetectionService::namesMatch(
        'JHAMELLIE ANNE P. VARGAS',
        'Jhamellie Anne P. Vargas',
    ))->toBeTrue();
});

test('different people do not match', function () {
    expect(FraudDetectionService::namesMatch(
        'VARGAS, JHAMELLIE ANNE P.',
        'Juan Dela Cruz',
    ))->toBeFalse();
});
