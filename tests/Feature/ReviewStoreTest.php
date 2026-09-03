<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証済みユーザーがレビューを投稿できる
     */
    public function test_authenticated_user_can_store_review(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('reviews.store', $book), [
            'rating' => 5,
            'comment' => 'とても面白い本でした！',
        ]);

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても面白い本でした！',
        ]);
    }

    /**
     * レビューの評価が1未満の場合、バリデーションエラーとなる
     */
    public function test_review_rating_must_be_at_least_one(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('reviews.store', $book), [
            'rating' => 0,
            'comment' => '評価範囲外のテスト',
        ]);

        $response->assertSessionHasErrors('rating');

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 0,
        ]);
    }

    /**
     * レビューの評価が5を超える場合、バリデーションエラーとなる
     */
    public function test_review_rating_must_not_exceed_five(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('reviews.store', $book), [
            'rating' => 6,
            'comment' => '評価範囲外のテスト',
        ]);

        $response->assertSessionHasErrors('rating');

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 6,
        ]);
    }

    /**
     * ゲストユーザーがレビューを投稿しようとした場合、ログインページにリダイレクトされる
     */
    public function test_guest_is_redirected_to_login_when_storing_review(): void
    {
        $book = Book::factory()->create();

        $response = $this->post(route('reviews.store', $book), [
            'rating' => 5,
            'comment' => 'ゲストからの投稿テスト',
        ]);

        $response->assertRedirect(route('login'));
    }
}
