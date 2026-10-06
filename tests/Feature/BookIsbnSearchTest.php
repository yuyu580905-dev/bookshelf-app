<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookIsbnSearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 正常なISBNからGoogle Books APIの書籍情報を取得できる。
     */
    public function test_isbn_search_returns_book_information(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 1,
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'テスト書籍',
                            'authors' => ['テスト著者'],
                            'publishedDate' => '2024-01-15',
                            'description' => 'テスト説明文です。',
                            'imageLinks' => [
                                'thumbnail' => 'https://example.com/image.jpg',
                            ],
                            'industryIdentifiers' => [
                                [
                                    'type' => 'ISBN_13',
                                    'identifier' => '9781234567890',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->getJson('/books/isbn/9781234567890');

        $response
            ->assertOk()
            ->assertJson([
                'title' => 'テスト書籍',
                'author' => 'テスト著者',
                'isbn' => '9781234567890',
                'description' => 'テスト説明文です。',
                'published_date' => '2024-01-15',
                'image_url' => 'https://example.com/image.jpg',
            ]);
    }

    /**
     * ISBNが13桁でない場合に400を返す。
     */
    public function test_isbn_search_returns_400_for_invalid_length(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/books/isbn/123456789012');

        $response
            ->assertStatus(400)
            ->assertJson([
                'error' => 'ISBNは13桁で入力してください。',
            ]);
    }

    /**
     * ISBNに数字以外が含まれる場合に400を返す。
     */
    public function test_isbn_search_returns_400_for_non_numeric_isbn(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/books/isbn/978123456789A');

        $response
            ->assertStatus(400)
            ->assertJson([
                'error' => 'ISBNは13桁で入力してください。',
            ]);
    }

    /**
     * Google Books APIに該当する書籍がない場合に404を返す。
     */
    public function test_isbn_search_returns_404_when_book_is_not_found(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 0,
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->getJson('/books/isbn/9781234567890');

        $response
            ->assertStatus(404)
            ->assertJson([
                'error' => '書籍が見つかりませんでした。',
            ]);
    }

    /**
     * 検索結果にISBN_13が一致する書籍がない場合に404を返す。
     */
    public function test_isbn_search_returns_404_when_isbn_does_not_match(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 1,
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => '別の書籍',
                            'authors' => ['別の著者'],
                            'industryIdentifiers' => [
                                [
                                    'type' => 'ISBN_13',
                                    'identifier' => '9789999999999',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->getJson('/books/isbn/9781234567890');

        $response
            ->assertStatus(404)
            ->assertJson([
                'error' => '書籍が見つかりませんでした。',
            ]);
    }

    /**
     * Google Books APIでエラーが発生した場合に500を返す。
     */
    public function test_isbn_search_returns_500_when_google_books_api_fails(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([], 500),
        ]);

        $response = $this->actingAs($user)
            ->getJson('/books/isbn/9781234567890');

        $response
            ->assertStatus(500)
            ->assertJson([
                'error' => 'API通信エラーが発生しました。',
            ]);
    }

    /**
     * Google Books APIのクォータ超過時は429を返す。
     */
    public function test_api_quota_exceeded_returns_429(): void
    {
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response(
                [],
                429
            ),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(
            route('books.isbn', ['isbn' => '9780262032933'])
        );

        $response->assertStatus(429);

        $response->assertJson([
            'error' => 'Google Books API のクォータを超過しました。.env に GOOGLE_BOOKS_API_KEY を設定してください。',
        ]);
    }
}
