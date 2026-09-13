<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookDeleteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 書籍を削除でき、204 No Contentが返る
     */
    public function test_book_can_be_deleted(): void
    {
        $book = Book::factory()->create();

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    /**
     * 存在しない書籍を削除しようとすると404が返る
     */
    public function test_delete_returns_404_for_non_existent_book(): void
    {
        $response = $this->deleteJson('/api/v1/books/99999');

        $response->assertNotFound();
    }

    /**
     * 書籍削除時に関連データも削除される
     */
    public function test_related_data_is_deleted_when_book_is_deleted(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $book->favoriteUsers()->attach($user->id);
        $book->genres()->attach($genre->id);

        $review->likedByUsers()->attach($user->id);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'review_id' => $review->id,
        ]);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    /**
     * 書籍削除時にジャンル本体は削除されない
     */
    public function test_genre_is_not_deleted_when_book_is_deleted(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $this->deleteJson("/api/v1/books/{$book->id}")
            ->assertNoContent();

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);
    }
}
