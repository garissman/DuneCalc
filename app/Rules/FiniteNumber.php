<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FiniteNumber implements ValidationRule
{
    /**
     * Rejects INF, -INF, and NaN — any value that is numeric but not finite.
     *
     * Precondition: apply this rule after the `numeric` rule. Non-numeric strings
     * are intentionally ignored here; the `numeric` rule handles that rejection.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_numeric($value) && ! is_finite((float) $value)) {
            $fail('The :attribute must be a finite number.');
        }
    }
}
