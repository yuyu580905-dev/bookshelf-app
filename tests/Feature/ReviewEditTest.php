<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewEditTest extends TestCase
{
    use RefreshDatabase;

    /**
     * レビュー投稿者本人が編集画面にアクセスできる
     */
    public function test_review_owner_can_access_edit_page(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reviews.edit', $review));

        $response->assertStatus(200);
        $response->assertViewIs('reviews.edit');
        $response->assertViewHas('review', $review);
    }

    /**
     * 他ユーザーが他人のレビュー編集画面にアクセスすると403になる
     */
    public function test_non_owner_cannot_access_edit_page(): void
    {
        $user = User::factory()->create();
        $reviewOwner = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $reviewOwner->id,
            'book_id' => $book->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reviews.edit', $review));

        $response->assertForbidden();
    }

    /**
     * ゲストユーザーがレビュー編集画面にアクセスするとログインページにリダイレクトされる
     */
    public function test_guest_is_redirected_to_login_when_accessing_edit_page(): void
    {
        $book = Book::factory()->create();
        $review = Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'book_id' => $book->id,
        ]);

        $response = $this->get(route('reviews.edit', $review));

        $response->assertRedirect(route('login'));
    }
}
