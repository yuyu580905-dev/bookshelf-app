<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookStoreRequest;
use App\Http\Requests\BookUpdateRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class BookController extends Controller
{
    public function index()
    {
        $query = Book::with('genres')
            ->withAvg('reviews', 'rating');

        if (request()->filled('keyword')) {
            $keyword = request('keyword');

            $query->where(function ($query) use ($keyword) {
                $query->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%");
            });
        }

        if (request()->filled('genre')) {
            $genre = request('genre');

            $query->whereHas('genres', function ($query) use ($genre) {
                $query->where('genres.id', $genre);
            });
        }

        $sort = request('sort', 'newest');

        switch ($sort) {
            case 'newest':
                $query->latest();
                break;

            case 'oldest':
                $query->oldest();
                break;

            case 'title':
                $query->orderBy('title');
                break;

            case 'rating':
                $query->orderByRaw('reviews_avg_rating IS NULL')
                    ->orderByDesc('reviews_avg_rating');
                break;

            default:
                $query->latest();
                break;
        }

        $books = $query
            ->paginate(10)
            ->withQueryString();

        $genres = Genre::all();

        return view('books.index', compact('books', 'genres'));
    }

    public function show(Book $book)
    {
        return view('books.show', compact('book'));
    }

    public function create()
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    /**
     * ISBNからGoogle Books APIで書籍情報を検索する。
     */
    public function searchByIsbn(string $isbn): JsonResponse
    {
        if (strlen($isbn) !== 13) {
            return response()->json([
                'error' => 'ISBNは13桁で入力してください。',
            ], 422);
        }

        if (! ctype_digit($isbn)) {
            return response()->json([
                'error' => 'ISBNは数字13桁で入力してください。',
            ], 422);
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Goog-Api-Key' => config('services.google_books.api_key'),
                ])
                ->get(
                    'https://www.googleapis.com/books/v1/volumes',
                    [
                        'q' => "isbn:{$isbn}",
                        'maxResults' => 10,
                    ]
                );
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Google Books APIとの通信に失敗しました。',
            ], 502);
        }

        if ($response->failed()) {
            return response()->json([
                'error' => 'Google Books APIとの通信に失敗しました。',
            ], 502);
        }

        $items = $response->json('items', []);

        $bookData = collect($items)->first(function (array $item) use ($isbn): bool {
            return collect($item['volumeInfo']['industryIdentifiers'] ?? [])
                ->contains(function (array $identifier) use ($isbn): bool {
                    return $identifier['type'] === 'ISBN_13'
                        && $identifier['identifier'] === $isbn;
                });
        });

        if ($bookData === null) {
            return response()->json([
                'error' => '該当する書籍が見つかりませんでした。',
            ], 404);
        }

        $volumeInfo = $bookData['volumeInfo'] ?? [];

        return response()->json([
            'title' => $volumeInfo['title'] ?? '',
            'author' => implode(', ', $volumeInfo['authors'] ?? []),
            'isbn' => $isbn,
            'description' => $volumeInfo['description'] ?? '',
            'published_date' => $volumeInfo['publishedDate'] ?? '',
            'image_url' => $volumeInfo['imageLinks']['thumbnail'] ?? '',
        ]);
    }

    public function store(BookStoreRequest $request)
    {
        $validated = $request->validated();

        $book = Book::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ]);

        $book->genres()->attach($validated['genres']);

        return redirect()->route('books.show', $book)->with('success', '書籍を登録しました。');
    }

    public function edit(Book $book)
    {
        $this->authorize('update', $book);

        $genres = Genre::all();

        return view('books.edit', compact('book', 'genres'));
    }

    public function update(BookUpdateRequest $request, Book $book)
    {
        $this->authorize('update', $book);

        $validated = $request->validated();

        $book->update([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ]);

        $book->genres()->sync($validated['genres']);

        return redirect()->route('books.show', $book)->with('success', '書籍を更新しました。');
    }

    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()->route('books.index')->with('success', '書籍を削除しました。');
    }
}
