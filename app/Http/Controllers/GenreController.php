<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenreStoreRequest;
use App\Models\Genre;

class GenreController extends Controller
{
    public function index()
    {
        $genres = Genre::withCount('books')->get();

        return view('genres.index', compact('genres'));
    }

    public function create()
    {
        return view('genres.create');
    }

    public function store(GenreStoreRequest $request)
    {
        Genre::create([
            'name' => $request->name,
        ]);

        return redirect()->route('genres.index')->with('success', 'ジャンルを追加しました。');
    }

    public function show(Genre $genre)
    {
        $books = $genre->books()
            ->with('genres')
            ->paginate(10);

        return view('genres.show', compact('genre', 'books'));
    }

    public function edit(Genre $genre)
    {
        return view('genres.edit', compact('genre'));
    }
}
