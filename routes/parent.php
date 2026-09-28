<?php

declare(strict_types=1);

use App\Http\Controllers\Parent\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:parent'])
    ->prefix('parent')
    ->name('parent.')
    ->group(function (): void {
        Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('analytics/students/{student}', [AnalyticsController::class, 'show'])->name('analytics.show');
        Route::put('analytics/students/{student}/goal', [AnalyticsController::class, 'updateGoal'])->name('analytics.goal');
    });
