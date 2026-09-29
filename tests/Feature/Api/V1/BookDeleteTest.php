<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookDeleteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証済みの所有者は書籍を削除できる。
     */
    public function test_owner_can_delete_book(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    /**
     * 未認証ユーザーは書籍を削除できない。
     */
    public function test_unauthenticated_user_cannot_delete_book(): void
    {
        $book = Book::factory()->create();

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => '認証が必要です。',
            ]);
    }

    /**
     * 他ユーザーの書籍は削除できない。
     */
    public function test_non_owner_cannot_delete_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response
            ->assertStatus(403)
            ->assertJson([
                'message' => 'この操作を実行する権限がありません。',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    /**
     * 存在しない書籍IDの場合は404を返す。
     */
    public function test_non_existing_book_returns_404(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/books/99999');

        $response
            ->assertStatus(404)
            ->assertJson([
                'message' => '書籍が見つかりませんでした。',
            ]);
    }

    /**
     * 書籍削除時に関連するレビューも削除される。
     */
    public function test_related_reviews_are_deleted_with_book(): void
    {
        $user = User::factory()->create();
        $reviewUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $review = Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $reviewUser->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    /**
     * 書籍削除時にジャンル自体は削除されない。
     */
    public function test_genres_are_not_deleted_with_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $book->genres()->attach($genre->id);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertNoContent();

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);
    }
}
