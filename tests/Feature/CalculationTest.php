<?php

declare(strict_types=1);

use App\Models\Calculation;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\HasPinnedSession;

uses(HasPinnedSession::class);

covers(
    \App\Http\Controllers\CalculationController::class,
    \App\Http\Requests\StoreCalculationRequest::class,
    \App\Http\Requests\UpdateCalculationRequest::class,
);

// --- INDEX ---

it('renders the calculator page with session calculations', function () {
    $own = Calculation::factory()->create(['session_id' => session()->getId()]);
    Calculation::factory()->create(['session_id' => 'other-session-00000000000000000000']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Calculator')
            ->has('calculations', 1)
            ->where('calculations.0.id', $own->id)
        );
});

it('returns calculations newest first', function () {
    $first = Calculation::factory()->create([
        'session_id' => session()->getId(),
        'created_at' => now()->subMinute(),
    ]);
    $second = Calculation::factory()->create([
        'session_id' => session()->getId(),
        'created_at' => now(),
    ]);

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('calculations.0.id', $second->id)
            ->where('calculations.1.id', $first->id)
        );
});

// --- STORE ---

it('stores a calculation for the current session', function () {
    $this->post(route('calculations.store'), [
        'expression' => '1 + 1',
        'result' => 2,
    ])->assertRedirect();

    $this->assertDatabaseHas('calculations', [
        'expression' => '1 + 1',
        'result' => 2,
    ]);
});

it('validates the store request', function () {
    $this->post(route('calculations.store'), [])
        ->assertSessionHasErrors(['expression', 'result']);
});

it('rejects a non-numeric result on store', function () {
    $this->post(route('calculations.store'), [
        'expression' => 'abc',
        'result' => 'not-a-number',
    ])->assertSessionHasErrors(['result']);
});

it('stores the session id on the calculation', function () {
    $this->post(route('calculations.store'), [
        'expression' => '5 * 5',
        'result' => 25,
    ]);

    $this->assertDatabaseHas('calculations', [
        'session_id' => session()->getId(),
        'expression' => '5 * 5',
    ]);
});

// --- UPDATE ---

it('updates a calculation that belongs to the current session', function () {
    $calculation = Calculation::factory()->create([
        'session_id' => session()->getId(),
        'expression' => '1 + 1',
        'result' => 2,
    ]);

    $this->put(route('calculations.update', $calculation), [
        'expression' => '2 + 2',
        'result' => 4,
    ])->assertRedirect();

    $this->assertDatabaseHas('calculations', [
        'id' => $calculation->id,
        'expression' => '2 + 2',
        'result' => 4,
    ]);
});

it('forbids updating a calculation from another session', function () {
    $calculation = Calculation::factory()->create([
        'session_id' => str_repeat('x', 40),
    ]);

    $this->put(route('calculations.update', $calculation), [
        'expression' => '2 + 2',
        'result' => 4,
    ])->assertForbidden();
});

it('validates the update request', function () {
    $calculation = Calculation::factory()->create(['session_id' => session()->getId()]);

    $this->put(route('calculations.update', $calculation), [])
        ->assertSessionHasErrors(['expression', 'result']);
});

// --- DESTROY ---

it('deletes a calculation that belongs to the current session', function () {
    $calculation = Calculation::factory()->create(['session_id' => session()->getId()]);

    $this->delete(route('calculations.destroy', $calculation))
        ->assertRedirect();

    $this->assertDatabaseMissing('calculations', ['id' => $calculation->id]);
});

it('forbids deleting a calculation from another session', function () {
    $calculation = Calculation::factory()->create([
        'session_id' => str_repeat('x', 40),
    ]);

    $this->delete(route('calculations.destroy', $calculation))
        ->assertForbidden();
});

// --- DESTROY ALL ---

it('clears all calculations for the current session', function () {
    Calculation::factory()->count(3)->create(['session_id' => session()->getId()]);
    $other = Calculation::factory()->create(['session_id' => str_repeat('z', 40)]);

    $this->delete(route('calculations.destroy-all'))
        ->assertRedirect();

    expect(Calculation::query()->where('session_id', session()->getId())->count())->toBe(0);
    $this->assertDatabaseHas('calculations', ['id' => $other->id]);
});

// --- STORE VALIDATION RULES ---

it('rejects a non-string expression on store', function () {
    $this->post(route('calculations.store'), [
        'expression' => ['array', 'value'],
        'result' => 2,
    ])->assertSessionHasErrors(['expression']);
});

it('rejects an expression longer than 500 characters on store', function () {
    $this->post(route('calculations.store'), [
        'expression' => str_repeat('a', 501),
        'result' => 2,
    ])->assertSessionHasErrors(['expression']);
});

it('accepts an expression exactly 500 characters on store', function () {
    $this->post(route('calculations.store'), [
        'expression' => str_repeat('a', 500),
        'result' => 2,
    ])->assertSessionDoesntHaveErrors(['expression']);
});

it('uses the custom expression required message on store', function () {
    $this->post(route('calculations.store'), [
        'result' => 2,
    ])->assertSessionHasErrors(['expression' => 'An expression is required.']);
});

it('uses the custom result required message on store', function () {
    $this->post(route('calculations.store'), [
        'expression' => '1 + 1',
    ])->assertSessionHasErrors(['result' => 'A result is required.']);
});

it('uses the custom result numeric message on store', function () {
    $this->post(route('calculations.store'), [
        'expression' => '1 + 1',
        'result' => 'not-a-number',
    ])->assertSessionHasErrors(['result' => 'The result must be a number.']);
});

it('rejects INF as result on store', function () {
    $this->post(route('calculations.store'), [
        'expression' => '1/0',
        'result' => INF,
    ])->assertSessionHasErrors(['result']);
});

it('rejects negative INF as result on store', function () {
    $this->post(route('calculations.store'), [
        'expression' => '-1/0',
        'result' => -INF,
    ])->assertSessionHasErrors(['result']);
});

it('rejects NaN as result on store', function () {
    $this->post(route('calculations.store'), [
        'expression' => '0/0',
        'result' => 'NaN',
    ])->assertSessionHasErrors(['result']);
});

// --- UPDATE VALIDATION RULES ---

it('rejects a non-string expression on update', function () {
    $calculation = Calculation::factory()->create(['session_id' => session()->getId()]);

    $this->put(route('calculations.update', $calculation), [
        'expression' => ['array', 'value'],
        'result' => 4,
    ])->assertSessionHasErrors(['expression']);
});

it('rejects an expression longer than 500 characters on update', function () {
    $calculation = Calculation::factory()->create(['session_id' => session()->getId()]);

    $this->put(route('calculations.update', $calculation), [
        'expression' => str_repeat('a', 501),
        'result' => 4,
    ])->assertSessionHasErrors(['expression']);
});

it('accepts an expression exactly 500 characters on update', function () {
    $calculation = Calculation::factory()->create(['session_id' => session()->getId()]);

    $this->put(route('calculations.update', $calculation), [
        'expression' => str_repeat('a', 500),
        'result' => 4,
    ])->assertSessionDoesntHaveErrors(['expression']);
});

it('rejects a non-numeric result on update', function () {
    $calculation = Calculation::factory()->create(['session_id' => session()->getId()]);

    $this->put(route('calculations.update', $calculation), [
        'expression' => '2 + 2',
        'result' => 'not-a-number',
    ])->assertSessionHasErrors(['result']);
});

it('uses the custom expression required message on update', function () {
    $calculation = Calculation::factory()->create(['session_id' => session()->getId()]);

    $this->put(route('calculations.update', $calculation), [
        'result' => 4,
    ])->assertSessionHasErrors(['expression' => 'An expression is required.']);
});

it('uses the custom result required message on update', function () {
    $calculation = Calculation::factory()->create(['session_id' => session()->getId()]);

    $this->put(route('calculations.update', $calculation), [
        'expression' => '2 + 2',
    ])->assertSessionHasErrors(['result' => 'A result is required.']);
});

it('uses the custom result numeric message on update', function () {
    $calculation = Calculation::factory()->create(['session_id' => session()->getId()]);

    $this->put(route('calculations.update', $calculation), [
        'expression' => '2 + 2',
        'result' => 'not-a-number',
    ])->assertSessionHasErrors(['result' => 'The result must be a number.']);
});

it('rejects INF as result on update', function () {
    $calculation = Calculation::factory()->create(['session_id' => session()->getId()]);

    $this->put(route('calculations.update', $calculation), [
        'expression' => '1/0',
        'result' => INF,
    ])->assertSessionHasErrors(['result']);
});

it('rejects negative INF as result on update', function () {
    $calculation = Calculation::factory()->create(['session_id' => session()->getId()]);

    $this->put(route('calculations.update', $calculation), [
        'expression' => '-1/0',
        'result' => -INF,
    ])->assertSessionHasErrors(['result']);
});

it('rejects NaN as result on update', function () {
    $calculation = Calculation::factory()->create(['session_id' => session()->getId()]);

    $this->put(route('calculations.update', $calculation), [
        'expression' => '0/0',
        'result' => 'NaN',
    ])->assertSessionHasErrors(['result']);
});

// --- SESSION ISOLATION ---

it('only returns calculations for the current session', function () {
    $this->post(route('calculations.store'), [
        'expression' => '1 + 1',
        'result' => 2,
    ]);

    Calculation::factory()->create(['session_id' => str_repeat('y', 40)]);

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('calculations', 1)
        );
});

// --- SNAPSHOT ---

it('returns calculations in a stable shape', function () {
    $this->travelTo('2025-01-01 00:00:00', function () {
        Calculation::factory()->create([
            'session_id' => session()->getId(),
            'expression' => '1 + 1',
            'result' => 2,
        ]);

        $response = $this->get(route('home'));

        expect($response->inertiaProps('calculations.0'))
            ->toMatchSnapshot();
    });
});
