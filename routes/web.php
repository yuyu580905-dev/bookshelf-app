<?php

use App\Http\Controllers\BooksController;
use Illuminate\Support\Facades\Route;

Route::get('/books/create', [BooksController::class, 'create'])->name('books.create');
