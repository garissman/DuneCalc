<?php

declare(strict_types=1);

use App\Models\Calculation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

covers(Calculation::class);

it('can be created via factory', function () {
    $calculation = Calculation::factory()->create();

    expect($calculation)->toBeInstanceOf(Calculation::class)
        ->and($calculation->session_id)->toBeString()->not->toBeEmpty()
        ->and($calculation->expression)->toBeString()->not->toBeEmpty()
        ->and($calculation->result)->toBeFloat();
});

it('casts result to a float', function () {
    $calculation = Calculation::factory()->create(['result' => '3.14159']);

    expect($calculation->result)->toBeFloat()->toBe(3.14159);
});

it('has the expected fillable attributes', function () {
    $calculation = new Calculation;

    expect($calculation->getFillable())->toBe(['session_id', 'expression', 'result']);
});

it('hides session_id from serialization', function () {
    $calculation = Calculation::factory()->make(['session_id' => 'secret-session-id-0000000000000000000']);

    $array = $calculation->toArray();

    expect($array)->not->toHaveKey('session_id');
});

it('scopes calculations to the current session', function () {
    $sessionA = str_repeat('a', 40);
    $sessionB = str_repeat('b', 40);

    Calculation::factory()->create(['session_id' => $sessionA]);
    Calculation::factory()->create(['session_id' => $sessionB]);

    $results = Calculation::query()->forSession($sessionA)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->session_id)->toBe($sessionA);
});
