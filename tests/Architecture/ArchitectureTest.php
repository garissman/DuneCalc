<?php

declare(strict_types=1);

arch('models only use allowed namespaces')
    ->expect('App\Models')
    ->toOnlyUse([
        'Illuminate\Database\Eloquent',
        'Illuminate\Database\Query',
        'Illuminate\Support',
        'Database\Factories',
    ]);

arch('models use HasFactory')
    ->expect('App\Models')
    ->toUse('Illuminate\Database\Eloquent\Factories\HasFactory');

arch('form requests extend FormRequest')
    ->expect('App\Http\Requests')
    ->toExtend('Illuminate\Foundation\Http\FormRequest');

arch('controllers only depend on allowed namespaces')
    ->expect('App\Http\Controllers')
    ->toOnlyUse([
        'App\Http\Requests',
        'App\Models',
        'Illuminate\Http',
        'Illuminate\Support',
        'Inertia',
        'session',
        'back',
        'abort_unless',
    ]);

arch('strict types are enforced on all app code')
    ->expect('App')
    ->toUseStrictTypes();
