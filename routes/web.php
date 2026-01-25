<?php

use App\Http\Controllers\Auth\DemoLoginController;
use App\Http\Controllers\BlendController;
use App\Http\Controllers\BottleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// Authenticated routes
Route::middleware('auth')
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        // Profile
        Route::controller(ProfileController::class)->group(function () {
            Route::get('/profile', 'edit')->name('profile.edit');
            Route::patch('/profile', 'update')->name('profile.update');
            Route::delete('/profile', 'destroy')->name('profile.destroy');
        });

        // Materials
        Route::resource('materials', MaterialController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

        // New bottle for a given material
        Route::resource('materials.bottles', BottleController::class)
            ->only(['create', 'store']);

        // editing / finishing / deleting an existing bottle
        Route::prefix('bottles/{bottle}')
            ->controller(BottleController::class)
            ->group(function () {
                Route::get('/edit', 'edit')->name('bottles.edit');
                Route::patch('/', 'update')->name('bottles.update');
                Route::post('/finish', 'finish')->name('bottles.finish');
                Route::post('/reactivate', 'reactivate')->name('bottles.reactivate');
                Route::delete('/', 'destroy')->name('bottles.destroy');
            });

        // Blends
        Route::resource('blends', BlendController::class)
            ->only(['create', 'store', 'show', 'destroy', 'edit', 'update']);

    });

Route::post('/demo-login', [DemoLoginController::class, 'store'])
    ->name('demo.login');

require __DIR__.'/auth.php';
