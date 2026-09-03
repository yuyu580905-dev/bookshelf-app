<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * レビュー投稿者本人がレビューを更新できる
     */
    public function test_review_owner_can_update_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '変更前のコメント',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('reviews.update', $review), [
                'rating' => 5,
                'comment' => '変更後のコメント',
            ]);

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '変更後のコメント',
        ]);
    }

    /**
     * 他ユーザーが他人のレビューを更新しようとした場合、403になる
     */
    public function test_non_owner_cannot_update_review(): void
    {
        $user = User::factory()->create();
        $reviewOwner = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $reviewOwner->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '変更前のコメント',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('reviews.update', $review), [
                'rating' => 5,
                'comment' => '変更しようとしたコメント',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '変更前のコメント',
        ]);
    }

    /**
     * ゲストユーザーがレビューを更新しようとした場合、ログインページにリダイレクトされる
     */
    public function test_guest_is_redirected_to_login_when_updating_review(): void
    {
        $book = Book::factory()->create();
        $review = Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '変更前のコメント',
        ]);

        $response = $this->put(route('reviews.update', $review), [
            'rating' => 5,
            'comment' => '変更しようとしたコメント',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '変更前のコメント',
        ]);
    }

    /**
     * バリデーションエラーの場合、レビューが更新されない
     */
    public function test_review_is_not_updated_with_invalid_data(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '変更前のコメント',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('reviews.update', $review), [
                'rating' => 6,
                'comment' => '変更しようとしたコメント',
            ]);

        $response->assertSessionHasErrors(['rating']);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '変更前のコメント',
        ]);
    }
}
