<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 書籍を正常に更新できる
     */
    public function test_book_can_be_updated(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $newGenre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '1111111111111',
        ]);
        $book->genres()->attach($genre);

        $data = [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '2222222222222',
            'published_date' => '2026-09-12',
            'description' => '更新後の説明',
            'image_url' => 'https://example.com/updated.jpg',
            'genres' => [$newGenre->id],
            'user_id' => $user->id,
        ];

        $response = $this->putJson("/api/v1/books/{$book->id}", $data);

        $response
            ->assertStatus(200)
            ->assertJsonPath('data.title', '更新後のタイトル')
            ->assertJsonPath('data.author', '更新後の著者')
            ->assertJsonPath('data.isbn', '2222222222222')
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '2222222222222',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenre->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    /**
     * 存在しない書籍IDの場合は404を返す
     */
    public function test_returns_404_for_nonexistent_book(): void
    {
        $genre = Genre::factory()->create();
        $user = User::factory()->create();

        $data = [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '2222222222222',
            'published_date' => '2026-09-12',
            'description' => '更新後の説明',
            'image_url' => 'https://example.com/updated.jpg',
            'genres' => [$genre->id],
            'user_id' => $user->id,
        ];

        $response = $this->putJson('/api/v1/books/9999', $data);

        $response->assertStatus(404);
    }

    /**
     * 自身のISBNは重複エラーにならない
     */
    public function test_current_isbn_can_be_kept(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '1111111111111',
        ]);

        $data = [
            'title' => '更新後のタイトル',
            'author' => $book->author,
            'isbn' => '1111111111111',
            'published_date' => $book->published_date,
            'description' => $book->description,
            'image_url' => $book->image_url,
            'genres' => [$genre->id],
            'user_id' => $user->id,
        ];

        $response = $this->putJson("/api/v1/books/{$book->id}", $data);

        $response
            ->assertStatus(200)
            ->assertJsonPath('data.isbn', '1111111111111');
    }

    /**
     * 他の書籍で使用されているISBNは422を返す
     */
    public function test_duplicate_isbn_returns_422(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '1111111111111',
        ]);

        $otherBook = Book::factory()->create([
            'isbn' => '2222222222222',
        ]);

        $data = [
            'title' => '更新後のタイトル',
            'author' => $book->author,
            'isbn' => $otherBook->isbn,
            'published_date' => $book->published_date,
            'description' => $book->description,
            'image_url' => $book->image_url,
            'genres' => [$genre->id],
            'user_id' => $user->id,
        ];

        $response = $this->putJson("/api/v1/books/{$book->id}", $data);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['isbn'])
            ->assertJsonPath(
                'errors.isbn.0',
                'そのISBNは既に使用されています。'
            );
    }

    /**
     * 必須項目が未入力の場合は422を返す
     */
    public function test_required_fields_return_422(): void
    {
        $book = Book::factory()->create();

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => '',
            'author' => '',
            'isbn' => '',
            'published_date' => '',
            'genres' => [],
            'user_id' => '',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'title',
                'author',
                'isbn',
                'published_date',
                'genres',
                'user_id',
            ]);
    }

    /**
     * 各フィールドの不正値の場合は422を返す
     */
    public function test_invalid_field_values_return_422(): void
    {
        $book = Book::factory()->create();

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => str_repeat('あ', 256),
            'author' => str_repeat('あ', 256),
            'isbn' => '123456789012',
            'published_date' => 'invalid-date',
            'description' => 123,
            'image_url' => 'invalid-url',
            'genres' => [Genre::factory()->create()->id],
            'user_id' => User::factory()->create()->id,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'title',
                'author',
                'isbn',
                'published_date',
                'description',
                'image_url',
            ]);
    }

    /**
     * genresのバリデーションエラーの場合は422を返す
     */
    public function test_invalid_genres_return_422(): void
    {
        $book = Book::factory()->create();
        $user = User::factory()->create();

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '2222222222222',
            'published_date' => '2026-09-12',
            'genres' => [9999],
            'user_id' => $user->id,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'genres.0' => '選択されたジャンルは存在しません。',
            ]);
    }

    /**
     * user_idのバリデーションエラーの場合は422を返す
     */
    public function test_invalid_user_id_return_422(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '2222222222222',
            'published_date' => '2026-09-12',
            'genres' => [$genre->id],
            'user_id' => 9999,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['user_id'])
            ->assertJsonPath(
                'errors.user_id.0',
                '指定された登録者は存在しません。'
            );
    }
}
