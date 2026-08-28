<?php

namespace App\Http\Controllers;

class BooksController extends Controller
{
    public function create()
    {
        return view('books.create');
    }
}
