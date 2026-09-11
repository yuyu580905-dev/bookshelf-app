<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 書籍詳細APIが認証なしでアクセスできる
     */
    public function test_books_show_api_can_be_accessed_without_authentication(): void
    {
        $book = Book::factory()->create();

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200);
    }

    /**
     * 書籍詳細APIが指定した書籍情報を返す
     */
    public function test_books_show_api_returns_book_information(): void
    {
        $book = Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn' => '1234567890123',
        ]);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.title', $book->title)
            ->assertJsonPath('data.author', $book->author)
            ->assertJsonPath('data.isbn', $book->isbn);
    }

    /**
     * 書籍詳細APIがジャンル情報を返す
     */
    public function test_books_show_api_returns_genre_information(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create([
            'name' => 'プログラミング',
        ]);

        $book->genres()->attach($genre);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.genres.0.id', $genre->id)
            ->assertJsonPath('data.genres.0.name', $genre->name);
    }

    /**
     * 書籍詳細APIがレビュー情報を返す
     */
    public function test_books_show_api_returns_review_information(): void
    {
        $book = Book::factory()->create();
        $user = User::factory()->create([
            'name' => '山田太郎',
        ]);

        $review = $book->reviews()->create([
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => 'とても面白い本でした。',
        ]);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.reviews.0.user_name', $user->name)
            ->assertJsonPath('data.reviews.0.rating', $review->rating)
            ->assertJsonPath('data.reviews.0.comment', $review->comment)
            ->assertJsonPath(
                'data.reviews.0.created_at',
                $review->created_at->toISOString()
            );
    }

    /**
     * 書籍詳細APIが平均評価とレビュー件数を返す
     */
    public function test_books_show_api_returns_average_rating_and_reviews_count(): void
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

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.average_rating', 4)
            ->assertJsonPath('data.reviews_count', 2);
    }

    /**
     * 存在しない書籍IDを指定した場合は404を返す
     */
    public function test_books_show_api_returns_404_for_nonexistent_book(): void
    {
        $response = $this->getJson('/api/v1/books/999999');

        $response->assertStatus(404);
    }
}
