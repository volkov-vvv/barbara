<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('apply', [ApplicationController::class, 'create'])->name('apply.create');
Route::post('apply', [ApplicationController::class, 'store'])->name('apply.store');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/student.php';
require __DIR__.'/parent.php';
