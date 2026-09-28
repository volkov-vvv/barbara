<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\ParentStudentController;
use App\Http\Controllers\Admin\StudentProgressController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WordController;
use App\Http\Controllers\Admin\WordSetController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('student-progress', [StudentProgressController::class, 'index'])->name('student-progress.index');
        Route::get('student-progress/{student}', [StudentProgressController::class, 'show'])->name('student-progress.show');

        Route::get('parent-students', [ParentStudentController::class, 'index'])->name('parent-students.index');
        Route::post('parent-students', [ParentStudentController::class, 'store'])->name('parent-students.store');
        Route::delete('parent-students/{parentStudent}', [ParentStudentController::class, 'destroy'])->name('parent-students.destroy');

        Route::get('languages', [LanguageController::class, 'index'])->name('languages.index');
        Route::post('languages', [LanguageController::class, 'store'])->name('languages.store');
        Route::put('languages/{language}', [LanguageController::class, 'update'])->name('languages.update');
        Route::delete('languages/{language}', [LanguageController::class, 'destroy'])->name('languages.destroy');

        Route::get('word-sets', [WordSetController::class, 'index'])->name('word-sets.index');
        Route::post('word-sets', [WordSetController::class, 'store'])->name('word-sets.store');
        Route::get('word-sets/{wordSet}', [WordSetController::class, 'show'])->name('word-sets.show');
        Route::put('word-sets/{wordSet}', [WordSetController::class, 'update'])->name('word-sets.update');
        Route::delete('word-sets/{wordSet}', [WordSetController::class, 'destroy'])->name('word-sets.destroy');

        Route::post('word-sets/{wordSet}/words', [WordController::class, 'store'])->name('words.store');
        Route::post('word-sets/{wordSet}/words/import', [WordController::class, 'import'])->name('words.import');
        Route::put('word-sets/{wordSet}/words/{word}', [WordController::class, 'update'])->name('words.update');
        Route::delete('word-sets/{wordSet}/words/{word}', [WordController::class, 'destroy'])->name('words.destroy');
    });
