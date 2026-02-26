<?php

declare(strict_types=1);

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Browser');

pest()->browser()->withHost(parse_url((string) (getenv('APP_URL') ?: 'http://localhost'), PHP_URL_HOST));

pest()->extend(Tests\TestCase::class)->in('Unit');

pest()->extend(Tests\TestCase::class)->in('Architecture');
