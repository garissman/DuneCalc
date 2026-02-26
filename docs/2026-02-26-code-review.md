# CalcTek Code Review — 2026-02-26

## Summary

Strong work overall. The architecture is sound, the testing philosophy is thorough, and the overall approach matches what a senior engineer would expect from a Laravel + Inertia + Vue stack. What follows is organized by severity.

---

## What Was Done Well

- `declare(strict_types=1)` on every PHP file
- `abort_unless` for session authorization, route-model binding
- Dedicated Form Requests with custom messages
- Custom `FiniteNumber` validation rule
- Named query scope (`scopeForSession`)
- `HasPinnedSession` trait for session isolation in tests
- Snapshot test for Inertia prop shape (catches API contract regressions)
- Testing custom validation messages explicitly (not just `assertSessionHasErrors`)
- CI matrix: PHP 8.4 (stable) + 8.5 (experimental, `continue-on-error`)
- Pint + Prettier enforced in CI
- Rate limiting on write routes, session ownership via `abort_unless`
- `session_id` hidden in `$hidden`

---

## Critical (Must Fix)

### 1. DRY violation — Form Requests are identical

`StoreCalculationRequest` and `UpdateCalculationRequest` have byte-for-byte identical `rules()` and `messages()` methods. Extract a shared `CalculationRequest` base class:

```php
// app/Http/Requests/CalculationRequest.php
abstract class CalculationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'expression' => ['required', 'string', 'max:500'],
            'result' => ['required', 'numeric', new FiniteNumber],
        ];
    }

    public function messages(): array
    {
        return [
            'expression.required' => 'An expression is required.',
            'result.required' => 'A result is required.',
            'result.numeric' => 'The result must be a number.',
        ];
    }
}
```

Then `StoreCalculationRequest extends CalculationRequest` and `UpdateCalculationRequest extends CalculationRequest` become nearly empty. Eliminates a maintenance hazard where one class gets updated and the other silently diverges.

---

### 2. `result` type inconsistency

The chain — DB `decimal(30,10)` → PHP `decimal:10` cast → JSON `string` → TypeScript `string` → `Number()` in template — works but is fragile and confusing. README still says "float" on the model table.

**Simplest fix:** Cast as `'float'` in the model, type as `number` in TypeScript, remove the `Number()` call in `Calculator.vue`.

**Alternative:** Keep `decimal:10` for precision, but add a comment in `calculation.ts` explaining why `result` is `string`, and update the README model table accordingly.

---

### 3. `window.confirm` is not production-appropriate

`window.confirm`:
- Cannot be styled
- Is suppressed in some browsers (iframes, mobile)
- Requires `window.confirm = () => true` hack in browser tests

Replace with an inline "Are you sure?" toggle in the Vue component — two buttons ("Confirm" / "Cancel") that appear after the first click on "Clear All".

---

## Important (Should Fix)

### 4. CI has no dependency caching

Every run does a full `composer install` + `npm ci`. Add caching:

```yaml
- name: Cache Composer dependencies
  uses: actions/cache@v4
  with:
    path: vendor
    key: composer-${{ hashFiles('composer.lock') }}

- name: Cache npm dependencies
  uses: actions/cache@v4
  with:
    path: ~/.npm
    key: npm-${{ hashFiles('package-lock.json') }}
```

---

### 5. Plan document is untracked

`docs/plans/2026-02-25-calculator-ui-redesign-plan.md` is untracked in git. Either commit it or add `docs/plans` to `.gitignore`. Leaving it untracked is a visible loose end.

---

### 6. Browser tests bypass aria-labels

Tests use raw JavaScript to click icon buttons instead of the aria-labels already on them:

```php
// Current — fragile
$page->script("document.querySelectorAll('button').forEach(b => { if (b.textContent.trim() === '✕') b.click(); })");

// Better — use the aria-label
$page->click('[aria-label="Delete 2 + 2"]');
```

---

### 7. `FiniteNumber` rule precondition is undocumented

The rule silently passes non-numeric strings, relying on the `numeric` rule preceding it in the array. Either make it self-contained (fail on non-numeric input too) or add a PHPDoc note stating the precondition: _"This rule must be applied after the `numeric` rule."_

---

## Suggestions (Nice to Have)

### 8. `aria-live` on the live result/error display

The `<p>` elements showing `liveError` and `liveResult` change dynamically but have no `aria-live` attribute. Screen readers will not announce updates without it.

```html
<div aria-live="polite">
    <p v-if="liveError" ...>{{ liveError }}</p>
    <p v-else-if="liveResult !== null" ...>= {{ liveResult }}</p>
</div>
```

---

### 9. `maxlength` on the expression input

The server rejects expressions over 500 chars. The input has no `maxlength` attribute, so feedback only comes after a round-trip. Add `maxlength="500"` to match the server constraint.

---

### 10. Inconsistent `:key` in `v-for` loops

`functionButtons` uses `:key="btn.value"` but `mainButtons` uses `:key="btn.label"`. Use `btn.value` consistently — it is the semantic identifier.

---

### 11. `CalculationFactory` silently excludes division

`fake()->randomElement(['+', '-', '*'])` omits `/` without a comment. A future developer adding `'/'` would get subtly wrong test data (PHP integer division vs. the decimal result stored in the DB).

---

### 12. Mutation tests removed from CI — mention locally

Mutation testing was removed from CI for speed/stability. Add a note in the README that they can be run locally:

```bash
./vendor/bin/pest --mutate --parallel
```

---

### 13. `cancelEditing` is a one-line wrapper

`cancelEditing()` only calls `clearExpression()`. Either document why it should remain a distinct function (e.g., future "unsaved changes" prompt), or call `clearExpression` directly in the template.

---

## Priority Order

| # | Issue | Effort | Impact |
|---|-------|--------|--------|
| 1 | Extract `CalculationRequest` base class | Low | High |
| 2 | Fix `result` type inconsistency + README | Low | High |
| 3 | Replace `window.confirm` with inline UI | Medium | High |
| 4 | Add CI dependency caching | Low | Medium |
| 5 | Commit or gitignore plan doc | Trivial | Medium |
| 6 | Use aria-labels in browser tests | Low | Medium |
| 7 | Document `FiniteNumber` precondition | Trivial | Medium |
| 8 | `aria-live` on result display | Trivial | Low |
| 9 | `maxlength` on expression input | Trivial | Low |
| 10 | Consistent `:key` in `v-for` | Trivial | Low |
| 11 | Comment in `CalculationFactory` re: division | Trivial | Low |
| 12 | Mention mutation tests in README | Trivial | Low |
| 13 | Remove/document `cancelEditing` wrapper | Trivial | Low |
