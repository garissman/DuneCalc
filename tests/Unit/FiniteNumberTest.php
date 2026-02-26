<?php

declare(strict_types=1);

use App\Rules\FiniteNumber;

covers(FiniteNumber::class);

it('passes validation for a finite number', function () {
    $rule = new FiniteNumber;
    $failed = false;

    $rule->validate('result', 42.5, function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

it('fails validation for positive infinity', function () {
    $rule = new FiniteNumber;
    $message = null;

    $rule->validate('result', INF, function (string $msg) use (&$message) {
        $message = $msg;
    });

    expect($message)->toBe('The :attribute must be a finite number.');
});

it('fails validation for negative infinity', function () {
    $rule = new FiniteNumber;
    $message = null;

    $rule->validate('result', -INF, function (string $msg) use (&$message) {
        $message = $msg;
    });

    expect($message)->toBe('The :attribute must be a finite number.');
});

it('does not fail for zero', function () {
    $rule = new FiniteNumber;
    $failed = false;

    $rule->validate('result', 0, function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

it('does not fail for non-numeric strings', function () {
    $rule = new FiniteNumber;
    $failed = false;

    $rule->validate('result', 'not-a-number', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

it('does not fail for the string "NaN" because is_numeric returns false for it', function () {
    // is_numeric('NaN') === false, so the numeric rule rejects it before FiniteNumber runs.
    // This test documents that FiniteNumber intentionally defers to the numeric rule.
    $rule = new FiniteNumber;
    $failed = false;

    $rule->validate('result', 'NaN', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});
