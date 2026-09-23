<?php

use App\Support\NameLetters;

test('different spacing and capitalization share the same letters', function () {
    expect(NameLetters::key('Juan dela Cruz'))
        ->toBe(NameLetters::key('JUAN DELA CRUZ'))
        ->toBe(NameLetters::key('juan  dela   cruz'))
        ->toBe(NameLetters::key('Juan-dela, Cruz'));
});

test('punctuation and accents do not change the letter sequence', function () {
    expect(NameLetters::key('Ma. Peña Jr.'))->toBe('mapenajr');
    expect(NameLetters::key('Jose Garcia'))->toBe(NameLetters::key('José García'));
});

test('a different letter makes a different key', function () {
    expect(NameLetters::key('Juan dela Cruz'))->not->toBe(NameLetters::key('Juan del Cruz'));
});

test('the same words in a different order are the same person', function () {
    expect(NameLetters::samePerson(
        'BLANQUERA JOHN LLOYD PACULAN',
        'JOHN LLOYD PACULAN BLANQUERA',
    ))->toBeTrue();

    expect(NameLetters::samePerson(
        'Cruz, Juan dela',
        'juan dela cruz',
    ))->toBeTrue();
});

test('leaving out a middle name is still the same person', function () {
    expect(NameLetters::samePerson(
        'BLANQUERA JOHN LLOYD',
        'JOHN LLOYD PACULAN BLANQUERA',
    ))->toBeTrue();

    expect(NameLetters::samePerson(
        'JOHN LLOYD BLANQUERA',
        'BLANQUERA JOHN LLOYD PACULAN',
    ))->toBeTrue();
});

test('one or two different letters are a near duplicate', function () {
    expect(NameLetters::nearDuplicateDistance(
        'JOHN LLOYD PACULAN BLANQUERA',
        'blanquera john lloyd pacilan',
    ))->toBe(1);

    expect(NameLetters::nearDuplicateDistance(
        'Juan dela Cruz',
        'Juan dela Crus',
    ))->toBe(1);
});

test('three different letters are not a near duplicate', function () {
    expect(NameLetters::nearDuplicateDistance(
        'JOHN LLOYD PACULAN BLANQUERA',
        'JUAN DELA CRUZ',
    ))->toBeNull();
});

test('a different person stays different', function () {
    expect(NameLetters::samePerson(
        'JOHN LLOYD PACULAN BLANQUERA',
        'JUAN DELA CRUZ',
    ))->toBeFalse();
});
