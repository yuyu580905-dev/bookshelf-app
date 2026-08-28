<?php

namespace App\Http\Controllers;

use App\Models\Book;

class BooksController extends Controller
{
    public function show(Book $book)
    {
        return view('books.show', compact('book'));
    }
}
