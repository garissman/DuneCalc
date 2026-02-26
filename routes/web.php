<?php

declare(strict_types=1);

use App\Http\Controllers\CalculationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CalculationController::class, 'index'])->name('home');

Route::middleware('throttle:60,1')->group(function () {
    Route::post('/calculations', [CalculationController::class, 'store'])->name('calculations.store');
    Route::put('/calculations/{calculation}', [CalculationController::class, 'update'])->name('calculations.update');
    Route::delete('/calculations/{calculation}', [CalculationController::class, 'destroy'])->name('calculations.destroy');
    Route::delete('/calculations', [CalculationController::class, 'destroyAll'])->name('calculations.destroy-all');
});
