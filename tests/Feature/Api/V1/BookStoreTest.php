<?php

namespace Tests\Feature\Api\V1;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証済みユーザーは書籍を登録できる。（201を返す）
     */
    public function test_authenticated_user_can_create_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        Sanctum::actingAs($user);

        $data = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000001',
            'published_date' => '2024-01-01',
            'description' => 'テスト説明',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        $response = $this->postJson('/api/v1/books', $data);

        $response
            ->assertStatus(201)
            ->assertJsonPath('data.title', 'テスト書籍');

        $this->assertDatabaseHas('books', [
            'title' => 'テスト書籍',
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $response->json('data.id'),
            'genre_id' => $genre->id,
        ]);
    }

    /**
     * 未認証ユーザーは書籍を登録できない。（401を返す）
     */
    public function test_unauthenticated_user_cannot_create_book(): void
    {
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000001',
            'published_date' => '2024-01-01',
            'description' => 'テスト説明',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        $response = $this->postJson('/api/v1/books', $data);

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => '認証が必要です。',
            ]);
    }

    /**
     * 必須項目がない場合は422を返す。
     */
    public function test_required_fields_return_422(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'title',
                'author',
                'isbn',
                'genres',
            ]);
    }

    /**
     * ISBNが13桁でない場合は422を返す。
     */
    public function test_invalid_isbn_returns_422(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        Sanctum::actingAs($user);

        $data = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '123',
            'published_date' => '2024-01-01',
            'genres' => [$genre->id],
        ];

        $response = $this->postJson('/api/v1/books', $data);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['isbn']);
    }

    /**
     * 存在しないジャンルIDの場合は422を返す。
     */
    public function test_invalid_genre_returns_422(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $data = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000001',
            'published_date' => '2024-01-01',
            'genres' => [99999],
        ];

        $response = $this->postJson('/api/v1/books', $data);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['genres.0']);
    }
}
