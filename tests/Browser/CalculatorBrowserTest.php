<?php

it('loads the calculator page', function (): void {
    $page = visit('/');

    $page->assertSee('CalcTek');
    $page->assertSee('History');
    $page->assertSee('No history yet.');
});

it('shows live result for a valid expression', function (): void {
    $page = visit('/');

    $page->type('[placeholder="Enter expression…"]', '2 + 3');
    $page->assertSee('= 5');
});

it('shows error for an invalid expression', function (): void {
    $page = visit('/');

    $page->type('[placeholder="Enter expression…"]', '2 +');
    $page->assertSee('Invalid expression');
});

it('submits a calculation and shows it in history', function (): void {
    $page = visit('/');

    $page->type('[placeholder="Enter expression…"]', '4 * 5');
    $page->assertSee('= 20');

    $page->keys('[placeholder="Enter expression…"]', 'Enter');

    $page->assertSee('4 * 5');
    $page->assertSee('20');
    $page->assertDontSee('No history yet.');
});

it('deletes a calculation from history', function (): void {
    $page = visit('/');

    $page->type('[placeholder="Enter expression…"]', '10 - 3');
    $page->keys('[placeholder="Enter expression…"]', 'Enter');

    $page->assertSee('10 - 3');
    $page->click('[aria-label="Delete 10 - 3"]');
    $page->assertSee('Sure?');
    $page->click('[aria-label="Confirm delete 10 - 3"]');

    $page->assertSee('No history yet.');
    $page->assertDontSee('10 - 3');
});

it('clears all history', function (): void {
    $page = visit('/');

    $page->type('[placeholder="Enter expression…"]', '1 + 1');
    $page->keys('[placeholder="Enter expression…"]', 'Enter');

    $page->assertSee('1 + 1');
    $page->assertSee('Clear All');

    $page->click('Clear All');
    $page->assertSee('Sure?');
    $page->click('[aria-label="Confirm clear all history"]');

    $page->assertSee('No history yet.');
    $page->assertDontSee('Clear All');
});

it('loads a calculation into edit mode', function (): void {
    $page = visit('/');

    $page->type('[placeholder="Enter expression…"]', '3 + 3');
    $page->keys('[placeholder="Enter expression…"]', 'Enter');

    $page->assertSee('3 + 3');
    $page->click('[aria-label="Edit 3 + 3"]');

    $page->assertValue('[placeholder="Enter expression…"]', '3 + 3');
    $page->assertSee('Update');
    $page->assertSee('Cancel');
});

it('appends operator via helper button', function (): void {
    $page = visit('/');

    $page->type('[placeholder="Enter expression…"]', '5');
    $page->click('[aria-label="+"]');

    $page->assertValue('[placeholder="Enter expression…"]', '5+');
});

it('evaluates a sqrt expression', function (): void {
    $page = visit('/');

    $page->click('√');
    $page->append('[placeholder="Enter expression…"]', '9)');

    $page->assertSee('= 3');
});

it('edits a calculation and updates it in history', function (): void {
    $page = visit('/');

    $page->type('[placeholder="Enter expression…"]', '2 + 2');
    $page->keys('[placeholder="Enter expression…"]', 'Enter');
    $page->assertSee('2 + 2');

    $page->click('[aria-label="Edit 2 + 2"]');
    $page->assertValue('[placeholder="Enter expression…"]', '2 + 2');
    $page->assertSee('Update');

    $page->clear('[placeholder="Enter expression…"]');
    $page->type('[placeholder="Enter expression…"]', '3 * 4');
    $page->assertSee('= 12');
    $page->click('Update');

    $page->assertSee('3 * 4');
    $page->assertSee('12');
    $page->assertDontSee('2 + 2');
    $page->assertDontSee('Update');
});

it('clears input with the C button', function (): void {
    $page = visit('/');

    $page->type('[placeholder="Enter expression…"]', '5 + 5');
    $page->assertSee('= 10');

    $page->click('[aria-label="Clear"]');

    $page->assertValue('[placeholder="Enter expression…"]', '');
    $page->assertDontSee('= 10');
});
