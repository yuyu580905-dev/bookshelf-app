<?php

namespace Tests\Feature;

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
     * 書籍作成者本人が書籍情報を削除できる（レビュー、いいね、ジャンルの関連データも削除されることを確認）
     */
    public function test_owner_can_delete_book_and_related_data(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()
            ->for($user)
            ->create();

        $genres = Genre::factory()->count(2)->create();
        $book->genres()->attach($genres);

        $review = Review::factory()
            ->for($book)
            ->for($otherUser)
            ->create();

        $book->favoriteUsers()->attach($otherUser);

        $review->likedByUsers()->attach($user);

        $response = $this->actingAs($user)
            ->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseMissing('favorites', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'review_id' => $review->id,
        ]);
    }

    /**
     * 他ユーザーが他人の書籍情報を削除しようとすると403になる
     */
    public function test_other_user_cannot_delete_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()
            ->for($owner)
            ->create();

        $response = $this->actingAs($otherUser)
            ->delete(route('books.destroy', $book));

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    /**
     * ゲストユーザーが書籍情報を削除しようとするとログインページにリダイレクトされる
     */
    public function test_guest_is_redirected_to_login_when_deleting_book(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()
            ->for($user)
            ->create();

        $response = $this->delete(route('books.destroy', $book));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }
}
