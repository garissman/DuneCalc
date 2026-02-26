<?php

use App\Models\Calculation;
use Tests\Concerns\HasPinnedSession;

uses(HasPinnedSession::class);

covers(\App\Http\Controllers\CalculationController::class);

it('handles storing 100 calculations in sequence', function () {
    $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

    for ($i = 1; $i <= 100; $i++) {
        $this->post(route('calculations.store'), [
            'expression' => "{$i} + {$i}",
            'result' => $i * 2,
        ])->assertRedirect();
    }

    expect(
        Calculation::query()->where('session_id', session()->getId())->count()
    )->toBe(100);
});

it('only loads calculations for the current session under load', function () {
    Calculation::factory()->count(50)->create(['session_id' => session()->getId()]);
    Calculation::factory()->count(50)->create(['session_id' => str_repeat('z', 40)]);

    $this->get(route('home'))
        ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->has('calculations', 50)
        );
});

it('clears 100 calculations at once', function () {
    Calculation::factory()->count(100)->create(['session_id' => session()->getId()]);

    $this->delete(route('calculations.destroy-all'))->assertRedirect();

    expect(
        Calculation::query()->where('session_id', session()->getId())->count()
    )->toBe(0);
});
