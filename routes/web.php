<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\RankingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BookController::class, 'index'])->name('books.index');
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
Route::get('/ranking', [RankingController::class, 'index'])->name('ranking.index');
Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
Route::post('/favorites/{book}/toggle', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
Route::get('/genres', [GenreController::class, 'index'])->name('genres.index');
Route::get('/genres/create', [GenreController::class, 'create'])->name('genres.create');
Route::get('/genres/{genre}', [GenreController::class, 'show'])->name('genres.show');
Route::get('/genres/{genre}/edit', [GenreController::class, 'edit'])->name('genres.edit');
Route::delete('/genres/{genre}', [GenreController::class, 'destroy'])->name('genres.destroy');
Route::post('/reviews/{review}/like', [BookController::class, 'likeReview'])->name('reviews.like');
Route::get('/reviews/{review}/edit', [BookController::class, 'editReview'])->name('reviews.store');

Route::middleware('auth')->group(function () {
    Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
});
