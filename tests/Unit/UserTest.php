<?php

declare(strict_types=1);

use App\Models\User;

covers(User::class);

it('has the expected fillable attributes', function () {
    $user = new User;

    expect($user->getFillable())->toBe(['name', 'email', 'password']);
});

it('hides password and remember_token from serialization', function () {
    $user = User::factory()->make([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'secret',
        'remember_token' => 'token123',
    ]);

    $array = $user->toArray();

    expect($array)->not->toHaveKey('password')
        ->and($array)->not->toHaveKey('remember_token');
});

it('casts email_verified_at as datetime', function () {
    $user = new User;

    expect($user->getCasts())->toHaveKey('email_verified_at', 'datetime');
});

it('casts password as hashed', function () {
    $user = new User;

    expect($user->getCasts())->toHaveKey('password', 'hashed');
});
