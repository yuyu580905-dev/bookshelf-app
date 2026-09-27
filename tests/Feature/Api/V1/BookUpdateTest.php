<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証済みの所有者は書籍を更新できる
     */
    public function test_owner_can_update_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $newGenre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $data = [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => $book->isbn,
            'published_date' => '2024-02-01',
            'description' => '更新後説明',
            'image_url' => 'https://example.com/updated.jpg',
            'genres' => [$newGenre->id],
        ];

        $response = $this->putJson("/api/v1/books/{$book->id}", $data);

        $response
            ->assertStatus(200)
            ->assertJsonPath('data.title', '更新後タイトル');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'user_id' => $user->id,
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
     * 未認証ユーザーは書籍を更新できない
     */
    public function test_unauthenticated_user_cannot_update_book(): void
    {
        $book = Book::factory()->create();

        $data = [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => $book->isbn,
            'published_date' => '2024-02-01',
            'genres' => [Genre::factory()->create()->id],
        ];

        $response = $this->putJson("/api/v1/books/{$book->id}", $data);

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => '認証が必要です。',
            ]);
    }

    /**
     * 所有者ではないユーザーは書籍を更新できない
     */
    public function test_non_owner_cannot_update_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        Sanctum::actingAs($otherUser);

        $data = [
            'title' => '不正な更新',
            'author' => '不正な著者',
            'isbn' => $book->isbn,
            'published_date' => '2024-02-01',
            'genres' => [Genre::factory()->create()->id],
        ];

        $response = $this->putJson("/api/v1/books/{$book->id}", $data);

        $response
            ->assertStatus(403)
            ->assertJson([
                'message' => 'この操作を実行する権限がありません。',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
        ]);
    }

    /**
     * 存在しない書籍IDの場合は404を返す
     */
    public function test_non_existing_book_returns_404(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $data = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000001',
            'published_date' => '2024-02-01',
            'genres' => [Genre::factory()->create()->id],
        ];

        $response = $this->putJson('/api/v1/books/99999', $data);

        $response
            ->assertStatus(404)
            ->assertJson([
                'message' => '書籍が見つかりませんでした。',
            ]);
    }

    /**
     * ISBNが13桁でない場合は422を返す
     */
    public function test_invalid_isbn_returns_422(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $data = [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => '123',
            'published_date' => '2024-02-01',
            'genres' => [Genre::factory()->create()->id],
        ];

        $response = $this->putJson("/api/v1/books/{$book->id}", $data);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['isbn']);
    }

    /**
     * 他の書籍と重複するISBNの場合は422を返す
     */
    public function test_duplicate_isbn_returns_422(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $anotherBook = Book::factory()->create();

        Sanctum::actingAs($user);

        $data = [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => $anotherBook->isbn,
            'published_date' => '2024-02-01',
            'genres' => [Genre::factory()->create()->id],
        ];

        $response = $this->putJson("/api/v1/books/{$book->id}", $data);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['isbn']);
    }

    /**
     * 必須項目がない場合は422を返す
     */
    public function test_required_fields_return_422(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/books/{$book->id}", []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'title',
                'author',
                'isbn',
                'published_date',
                'genres',
            ]);
    }

    /**
     * 不正なジャンルIDの場合は422を返す
     */
    public function test_invalid_genre_returns_422(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $data = [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => $book->isbn,
            'published_date' => '2024-02-01',
            'genres' => [99999],
        ];

        $response = $this->putJson("/api/v1/books/{$book->id}", $data);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['genres.0']);
    }
}
