<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\ParentStudentController;
use App\Http\Controllers\Admin\StudentProgressController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WordController;
use App\Http\Controllers\Admin\WordSetController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::resource('users', UserController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::get('applications/export/excel', [ApplicationController::class, 'exportExcel'])
            ->name('applications.export.excel');
        Route::get('applications/export/pdf', [ApplicationController::class, 'exportPdf'])
            ->name('applications.export.pdf');

        Route::resource('applications', ApplicationController::class)
            ->only(['index', 'show', 'update', 'destroy']);

        Route::get('applications/{application}/document', [ApplicationController::class, 'download'])
            ->name('applications.download');

        Route::resource('student-progress', StudentProgressController::class)
            ->only(['index', 'show'])
            ->parameters(['student-progress' => 'student']);

        Route::resource('parent-students', ParentStudentController::class)
            ->only(['index', 'store', 'destroy'])
            ->parameters(['parent-students' => 'parentStudent']);

        Route::resource('languages', LanguageController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('themes', ThemeController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::get('word-sets/{wordSet}/view', [WordSetController::class, 'view'])
            ->name('word-sets.view');
        Route::get('word-sets/{wordSet}/view/export/excel', [WordSetController::class, 'viewExportExcel'])
            ->name('word-sets.view.export.excel');
        Route::get('word-sets/{wordSet}/view/export/pdf', [WordSetController::class, 'viewExportPdf'])
            ->name('word-sets.view.export.pdf');
        Route::get('word-sets/{wordSet}/export', [WordSetController::class, 'exportOptions'])
            ->name('word-sets.export.options');
        Route::get('word-sets/{wordSet}/export/pdf', [WordSetController::class, 'exportPdf'])
            ->name('word-sets.export.pdf');

        Route::resource('word-sets', WordSetController::class)
            ->only(['index', 'store', 'show', 'update', 'destroy'])
            ->parameters(['word-sets' => 'wordSet']);

        Route::post('word-sets/{wordSet}/words/import', [WordController::class, 'import'])
            ->name('words.import');

        Route::resource('word-sets.words', WordController::class)
            ->only(['show', 'store', 'update', 'destroy'])
            ->parameters([
                'word-sets' => 'wordSet',
                'words' => 'word',
            ])
            ->names([
                'show' => 'words.show',
                'store' => 'words.store',
                'update' => 'words.update',
                'destroy' => 'words.destroy',
            ]);
    });
