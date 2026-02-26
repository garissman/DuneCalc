<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Calculation>
 */
class CalculationFactory extends Factory
{
    /**
     * @return array{session_id: string, expression: string, result: int}
     */
    public function definition(): array
    {
        $a = fake()->numberBetween(1, 100);
        $b = fake()->numberBetween(1, 100);
        // Division is excluded to avoid non-integer results that would not
        // match the integer arithmetic used to compute $result below.
        $operator = fake()->randomElement(['+', '-', '*']);

        $result = match ($operator) {
            '+' => $a + $b,
            '-' => $a - $b,
            '*' => $a * $b,
        };

        return [
            'session_id' => fake()->uuid(),
            'expression' => "{$a} {$operator} {$b}",
            'result' => $result,
        ];
    }
}
