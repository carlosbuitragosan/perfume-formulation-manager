<?php

use App\Http\Controllers\Auth\DemoLoginController;
use App\Http\Controllers\BlendController;
use App\Http\Controllers\BlendIngredientController;
use App\Http\Controllers\BlendVersionController;
use App\Http\Controllers\BottleController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('blends.index');
});

// Authenticated routes
Route::middleware('auth')
    ->group(function () {
        // Profile
        Route::controller(ProfileController::class)
            ->group(function () {
                Route::get('/profile', 'edit')->name('profile.edit');
                Route::patch('/profile', 'update')->name('profile.update');
                Route::delete('/profile', 'destroy')->name('profile.destroy');
            });

        // Materials
        Route::resource('materials', MaterialController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

        // Bottles (create, store)
        Route::resource('materials.bottles', BottleController::class)
            ->only(['create', 'store']);

        // (edit, finish, delete )
        Route::prefix('bottles/{bottle}')
            ->controller(BottleController::class)
            ->group(function () {
                Route::get('/edit', 'edit')->name('bottles.edit');
                Route::patch('/', 'update')->name('bottles.update');
                Route::post('/finish', 'finish')->name('bottles.finish');
                Route::post('/unfinish', 'unfinish')->name('bottles.unfinish');
                Route::delete('/', 'destroy')->name('bottles.destroy');
            });

        // Blends
        Route::resource('blends', BlendController::class)
            ->only(['index', 'create', 'store', 'show', 'destroy', 'update']);

        // Blend versions
        Route::resource('blends.versions', BlendVersionController::class)
            ->only(['create', 'store', 'update']);

        // Blend ingredients
        Route::post('/blend-ingredients/{blendIngredient}/bottles/{bottle}',
            [BlendIngredientController::class, 'assignBottle'])
            ->name('blend-ingredients.assign-bottle');
    });

Route::post('/demo-login', [DemoLoginController::class, 'store'])
    ->name('demo.login');

require __DIR__.'/auth.php';
