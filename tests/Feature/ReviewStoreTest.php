<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Database\Seeders\BookSeeder;
use Database\Seeders\GenreSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewStoreTest extends TestCase
{
    use RefreshDatabase;

    // 認証済みユーザーがレビューを投稿できることを確認するテスト
    public function test_authenticated_user_can_store_review(): void
    {
        $this->seed([
            UserSeeder::class,
            GenreSeeder::class,
            BookSeeder::class,
        ]);

        $user = User::first();
        $book = Book::first();

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

    // レビューの評価が1未満の場合、バリデーションエラーとなることを確認するテスト
    public function test_review_rating_must_be_at_least_one(): void
    {
        $this->seed([
            UserSeeder::class,
            GenreSeeder::class,
            BookSeeder::class,
        ]);

        $user = User::first();
        $book = Book::first();

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

    // レビューの評価が5を超える場合、バリデーションエラーとなることを確認するテスト
    public function test_review_rating_must_not_exceed_five(): void
    {
        $this->seed([
            UserSeeder::class,
            GenreSeeder::class,
            BookSeeder::class,
        ]);

        $user = User::first();
        $book = Book::first();

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

    // ゲストユーザーがレビューを投稿しようとした場合、ログインページにリダイレクトされることを確認するテスト
    public function test_guest_is_redirected_to_login_when_storing_review(): void
    {
        $this->seed([
            UserSeeder::class,
            GenreSeeder::class,
            BookSeeder::class,
        ]);

        $book = Book::first();

        $response = $this->post(route('reviews.store', $book), [
            'rating' => 5,
            'comment' => 'ゲストからの投稿テスト',
        ]);

        $response->assertRedirect(route('login'));
    }
}
