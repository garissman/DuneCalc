<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\FiniteNumber;
use Illuminate\Foundation\Http\FormRequest;

abstract class CalculationRequest extends FormRequest
{
    /**
     * Allow all requests — session ownership is enforced in the controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules shared by store and update requests.
     *
     * @return array<string, list<string|\Illuminate\Contracts\Validation\ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'expression' => ['required', 'string', 'max:500'],
            'result' => ['required', 'numeric', new FiniteNumber],
        ];
    }

    /**
     * Custom error messages for validation failures.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'expression.required' => 'An expression is required.',
            'result.required' => 'A result is required.',
            'result.numeric' => 'The result must be a number.',
        ];
    }
}
