<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BooksController;

Route::get('/books/{book}', [BooksController::class, 'show'])->name('books.show');
