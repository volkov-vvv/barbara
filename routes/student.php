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
        Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
        Route::post('reviews', [ReviewController::class, 'store'])->name('reviews.store');
        Route::post('reviews/more', [ReviewController::class, 'requestMore'])->name('reviews.more');

        Route::get('word-sets', [WordSetController::class, 'index'])->name('word-sets.index');
        Route::post('word-sets', [WordSetController::class, 'store'])->name('word-sets.store');
        Route::get('word-sets/{wordSet}', [WordSetController::class, 'show'])->name('word-sets.show');
        Route::put('word-sets/{wordSet}', [WordSetController::class, 'update'])->name('word-sets.update');
        Route::delete('word-sets/{wordSet}', [WordSetController::class, 'destroy'])->name('word-sets.destroy');

        Route::post('word-sets/{wordSet}/words', [WordController::class, 'store'])->name('words.store');
        Route::put('word-sets/{wordSet}/words/{word}', [WordController::class, 'update'])->name('words.update');
        Route::delete('word-sets/{wordSet}/words/{word}', [WordController::class, 'destroy'])->name('words.destroy');
    });
