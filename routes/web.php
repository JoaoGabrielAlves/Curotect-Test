<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Broadcasting routes for channel authorization
Broadcast::routes(['middleware' => ['web', 'auth']]);

// Welcome page
Route::get('/', [DashboardController::class, 'welcome'])->name('home');

// Dashboard
Route::get('dashboard', [DashboardController::class, 'dashboard'])
    ->middleware(['auth'])
    ->name('dashboard');

// Include modular route files
require __DIR__.'/posts.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/channels.php';
