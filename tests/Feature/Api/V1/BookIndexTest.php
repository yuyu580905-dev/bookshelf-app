<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 書籍一覧APIが認証なしでアクセスできる
     */
    public function test_books_index_api_can_be_accessed_without_authentication(): void
    {
        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(200);
    }

    /**
     * 書籍一覧APIが書籍情報を返す
     */
    public function test_books_index_api_returns_book_information(): void
    {
        $book = Book::factory()->create();

        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $book->id)
            ->assertJsonPath('data.0.title', $book->title)
            ->assertJsonPath('data.0.author', $book->author)
            ->assertJsonPath('data.0.isbn', $book->isbn);
    }

    /**
     * 書籍一覧APIがジャンル情報を返す
     */
    public function test_books_index_api_returns_genre_information(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre);

        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.genres.0.id', $genre->id)
            ->assertJsonPath('data.0.genres.0.name', $genre->name);
    }

    /**
     * 書籍一覧APIが平均評価を返す
     */
    public function test_books_index_api_returns_average_rating(): void
    {
        $book = Book::factory()->create();

        $book->reviews()->create([
            'user_id' => User::factory()->create()->id,
            'rating' => 5,
            'comment' => 'とても良い本でした。',
        ]);

        $book->reviews()->create([
            'user_id' => User::factory()->create()->id,
            'rating' => 3,
            'comment' => '普通でした。',
        ]);

        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.average_rating', 4);
    }

    /**
     * 書籍一覧APIがレビュー件数を返す
     */
    public function test_books_index_api_returns_reviews_count(): void
    {
        $book = Book::factory()->create();

        $book->reviews()->create([
            'user_id' => User::factory()->create()->id,
            'rating' => 5,
            'comment' => 'とても良い本でした。',
        ]);

        $book->reviews()->create([
            'user_id' => User::factory()->create()->id,
            'rating' => 3,
            'comment' => '普通でした。',
        ]);

        $book->reviews()->create([
            'user_id' => User::factory()->create()->id,
            'rating' => 4,
            'comment' => '面白かったです。',
        ]);

        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.reviews_count', 3);
    }

    /**
     * キーワードで書籍を検索できる（タイトル検索）
     */
    public function test_books_index_api_can_search_books_by_keyword(): void
    {
        $matchedBook = Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        $unmatchedBook = Book::factory()->create([
            'title' => 'JavaScript入門',
            'author' => '佐藤花子',
        ]);

        $response = $this->getJson('/api/v1/books?keyword=Laravel');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $matchedBook->id,
                'title' => $matchedBook->title,
            ])
            ->assertJsonMissing([
                'id' => $unmatchedBook->id,
                'title' => $unmatchedBook->title,
            ]);
    }

    /**
     * 著者名でもキーワード検索できる
     */
    public function test_books_index_api_can_search_books_by_author_keyword(): void
    {
        $matchedBook = Book::factory()->create([
            'title' => 'PHP入門',
            'author' => '山田太郎',
        ]);

        $unmatchedBook = Book::factory()->create([
            'title' => 'JavaScript入門',
            'author' => '佐藤花子',
        ]);

        $response = $this->getJson('/api/v1/books?keyword=山田太郎');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $matchedBook->id,
                'title' => $matchedBook->title,
            ])
            ->assertJsonMissing([
                'id' => $unmatchedBook->id,
                'title' => $unmatchedBook->title,
            ]);
    }

    /**
     * ジャンルIDで書籍を絞り込める
     */
    public function test_books_index_api_can_filter_books_by_genre_id(): void
    {
        $genre = Genre::factory()->create();
        $otherGenre = Genre::factory()->create();

        $matchedBook = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $unmatchedBook = Book::factory()->create([
            'title' => 'JavaScript入門',
        ]);

        $matchedBook->genres()->attach($genre);
        $unmatchedBook->genres()->attach($otherGenre);

        $response = $this->getJson('/api/v1/books?genre_id='.$genre->id);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $matchedBook->id,
                'title' => $matchedBook->title,
            ])
            ->assertJsonMissing([
                'id' => $unmatchedBook->id,
                'title' => $unmatchedBook->title,
            ]);
    }

    /**
     * キーワードは文字列でなければならない
     */
    public function test_keyword_must_be_a_string(): void
    {
        $response = $this->getJson('/api/v1/books?keyword[]=Laravel');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['keyword']);
    }

    /**
     * キーワードは255文字以内でなければならない
     */
    public function test_keyword_must_not_exceed_255_characters(): void
    {
        $keyword = str_repeat('a', 256);

        $response = $this->getJson('/api/v1/books?keyword='.$keyword);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['keyword']);
    }

    /**
     * ジャンルIDは整数でなければならない
     */
    public function test_genre_id_must_be_an_integer(): void
    {
        $response = $this->getJson('/api/v1/books?genre_id=abc');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['genre_id']);
    }

    /**
     * ジャンルIDは存在するジャンルでなければならない
     */
    public function test_genre_id_must_exist(): void
    {
        $response = $this->getJson('/api/v1/books?genre_id=999999');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['genre_id']);
    }

    /**
     * ページ番号は整数でなければならない
     */
    public function test_page_must_be_an_integer(): void
    {
        $response = $this->getJson('/api/v1/books?page=abc');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['page']);
    }

    /**
     * ページ番号は1以上でなければならない
     */
    public function test_page_must_be_at_least_one(): void
    {
        $response = $this->getJson('/api/v1/books?page=0');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['page']);
    }

    /**
     * 1ページあたりの件数は整数でなければならない
     */
    public function test_per_page_must_be_an_integer(): void
    {
        $response = $this->getJson('/api/v1/books?per_page=abc');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    /**
     * 1ページあたりの件数は1以上でなければならない
     */
    public function test_per_page_must_be_at_least_one(): void
    {
        $response = $this->getJson('/api/v1/books?per_page=0');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    /**
     * 1ページあたりの件数は100以下でなければならない
     */
    public function test_per_page_must_not_exceed_100(): void
    {
        $response = $this->getJson('/api/v1/books?per_page=101');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    /**
     * 1ページあたりの件数を指定できる
     */
    public function test_books_index_api_can_set_per_page(): void
    {
        Book::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/books?per_page=2');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}
