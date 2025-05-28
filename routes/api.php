<?php

use App\Http\Controllers\Api\PostController as ApiPostController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('posts')->group(function () {
    Route::post('/', [ApiPostController::class, 'store'])->name('api.posts.store');
    Route::put('/{post}', [ApiPostController::class, 'update'])->name('api.posts.update');
    Route::delete('/{post}', [ApiPostController::class, 'destroy'])->name('api.posts.destroy');
});
