<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Database\Console\Migrations\RollbackCommand;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

covers(AppServiceProvider::class);

afterEach(function () {
    $this->app['env'] = 'testing';
});

it('configures immutable dates', function () {
    expect(now())->toBeInstanceOf(\Carbon\CarbonImmutable::class);
});

it('prohibits destructive migration commands in production', function () {
    $this->app['env'] = 'production';

    FreshCommand::prohibit(false);
    RollbackCommand::prohibit(false);

    $provider = new AppServiceProvider($this->app);
    $provider->boot();

    $freshProp = (new ReflectionClass(FreshCommand::class))->getProperty('prohibitedFromRunning');
    $freshProp->setAccessible(true);
    $rollbackProp = (new ReflectionClass(RollbackCommand::class))->getProperty('prohibitedFromRunning');
    $rollbackProp->setAccessible(true);

    expect((bool) $freshProp->getValue())->toBeTrue()
        ->and((bool) $rollbackProp->getValue())->toBeTrue();

    FreshCommand::prohibit(false);
    RollbackCommand::prohibit(false);
});

it('does not prohibit destructive migration commands outside production', function () {
    $this->app['env'] = 'testing';

    FreshCommand::prohibit(false);
    RollbackCommand::prohibit(false);

    $provider = new AppServiceProvider($this->app);
    $provider->boot();

    $freshProp = (new ReflectionClass(FreshCommand::class))->getProperty('prohibitedFromRunning');
    $freshProp->setAccessible(true);
    $rollbackProp = (new ReflectionClass(RollbackCommand::class))->getProperty('prohibitedFromRunning');
    $rollbackProp->setAccessible(true);

    expect((bool) $freshProp->getValue())->toBeFalse()
        ->and((bool) $rollbackProp->getValue())->toBeFalse();
});

it('requires a minimum of 12 characters for passwords in production', function () {
    $this->app['env'] = 'production';

    $provider = new AppServiceProvider($this->app);
    $provider->boot();

    $elevenChars = Validator::make(
        ['password' => 'Abcdef1!ghi'],
        ['password' => Password::default()],
    );

    $twelveChars = Validator::make(
        ['password' => 'Abcdef1!ghij'],
        ['password' => Password::default()],
    );

    expect($elevenChars->fails())->toBeTrue()
        ->and($twelveChars->fails())->toBeFalse();
});

it('rejects passwords without mixed case in production', function () {
    $this->app['env'] = 'production';

    $provider = new AppServiceProvider($this->app);
    $provider->boot();

    $validator = Validator::make(
        ['password' => 'abcdef1!ghij'],
        ['password' => Password::default()],
    );

    expect($validator->fails())->toBeTrue();
});

it('rejects passwords without numbers in production', function () {
    $this->app['env'] = 'production';

    $provider = new AppServiceProvider($this->app);
    $provider->boot();

    $validator = Validator::make(
        ['password' => 'Abcdefg!hijk'],
        ['password' => Password::default()],
    );

    expect($validator->fails())->toBeTrue();
});

it('rejects passwords without symbols in production', function () {
    $this->app['env'] = 'production';

    $provider = new AppServiceProvider($this->app);
    $provider->boot();

    $validator = Validator::make(
        ['password' => 'Abcdefg1hijk'],
        ['password' => Password::default()],
    );

    expect($validator->fails())->toBeTrue();
});

it('does not enforce strong password rules outside production', function () {
    $this->app['env'] = 'testing';

    $provider = new AppServiceProvider($this->app);
    $provider->boot();

    $validator = Validator::make(
        ['password' => 'abcdef1!ghij'],
        ['password' => Password::default()],
    );

    expect($validator->fails())->toBeFalse();
});
