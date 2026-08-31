<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_like_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)->post(
            route('reviews.like', $review)
        );

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_authenticated_user_can_unlike_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'book_id' => $book->id,
        ]);

        $user->likedReviews()->attach($review->id);

        $response = $this->actingAs($user)->post(
            route('reviews.like', $review)
        );

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_guest_is_redirected_to_login_when_liking_review(): void
    {
        $book = Book::factory()->create();
        $review = Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'book_id' => $book->id,
        ]);

        $response = $this->post(
            route('reviews.like', $review)
        );

        $response->assertRedirect(route('login'));
    }
}
