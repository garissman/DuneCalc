<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCalculationRequest;
use App\Http\Requests\UpdateCalculationRequest;
use App\Models\Calculation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CalculationController extends Controller
{
    /**
     * Display a listing of the user's calculations.
     *
     * @return Response Inertia response rendering the Calculator page with session-scoped calculations.
     */
    public function index(): Response
    {
        $calculations = Calculation::forSession(session()->getId())
            ->latest()
            ->get();

        return Inertia::render('Calculator', [
            'calculations' => $calculations,
        ]);
    }

    /**
     * Store a new calculation for the current session.
     *
     * @param  StoreCalculationRequest  $request  Validated request containing expression and result.
     * @return RedirectResponse Redirects back to the calculator page.
     */
    public function store(StoreCalculationRequest $request): RedirectResponse
    {
        Calculation::create([
            'session_id' => session()->getId(),
            'expression' => $request->validated('expression'),
            'result' => $request->validated('result'),
        ]);

        return back();
    }

    /**
     * Update an existing calculation. Aborts with 403 if the record belongs to a different session.
     *
     * @param  UpdateCalculationRequest  $request  Validated request containing expression and result.
     * @param  Calculation  $calculation  Route-model-bound calculation to update.
     * @return RedirectResponse Redirects back to the calculator page.
     */
    public function update(UpdateCalculationRequest $request, Calculation $calculation): RedirectResponse
    {
        abort_unless($calculation->session_id === session()->getId(), 403);

        $calculation->update([
            'expression' => $request->validated('expression'),
            'result' => $request->validated('result'),
        ]);

        return back();
    }

    /**
     * Delete a single calculation. Aborts with 403 if the record belongs to a different session.
     *
     * @param  Calculation  $calculation  Route-model-bound calculation to delete.
     * @return RedirectResponse Redirects back to the calculator page.
     */
    public function destroy(Calculation $calculation): RedirectResponse
    {
        abort_unless($calculation->session_id === session()->getId(), 403);

        $calculation->delete();

        return back();
    }

    /**
     * Delete all calculations belonging to the current session.
     *
     * @return RedirectResponse Redirects back to the calculator page.
     */
    public function destroyAll(): RedirectResponse
    {
        Calculation::forSession(session()->getId())->delete();

        return back();
    }
}
