<?php

declare(strict_types=1);

use App\Http\Controllers\Student\ReviewController;
use App\Http\Controllers\Student\WordController;
use App\Http\Controllers\Student\WordSetController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function (): void {
        Route::post('reviews/more', [ReviewController::class, 'requestMore'])
            ->name('reviews.more');

        Route::resource('reviews', ReviewController::class)
            ->only(['index', 'store']);

        Route::resource('word-sets', WordSetController::class)
            ->only(['index', 'store', 'show', 'update', 'destroy'])
            ->parameters(['word-sets' => 'wordSet']);

        Route::resource('word-sets.words', WordController::class)
            ->only(['store', 'update', 'destroy'])
            ->parameters([
                'word-sets' => 'wordSet',
                'words' => 'word',
            ])
            ->names([
                'store' => 'words.store',
                'update' => 'words.update',
                'destroy' => 'words.destroy',
            ]);
    });
