<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewDeleteTest extends TestCase
{
    use RefreshDatabase;

    // レビュー投稿者本人がレビューを削除できることを確認するテスト
    public function test_review_owner_can_delete_their_review(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('reviews.destroy', $review));

        $response->assertRedirect(route('books.show', $review->book));

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    // レビュー削除時に関連するいいねも削除されることを確認するテスト
    public function test_deleting_review_also_deletes_related_likes(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        $likedUser = User::factory()->create();

        $review->likedByUsers()->attach($likedUser->id);

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $likedUser->id,
            'review_id' => $review->id,
        ]);

        $this->actingAs($user)
            ->delete(route('reviews.destroy', $review));

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $likedUser->id,
            'review_id' => $review->id,
        ]);
    }

    // 他ユーザーが他人のレビューを削除しようとした場合、403になることを確認するテスト
    public function test_non_owner_cannot_delete_review(): void
    {
        $user = User::factory()->create();
        $reviewOwner = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $reviewOwner->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('reviews.destroy', $review));

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $reviewOwner->id,
        ]);
    }

    // ゲストユーザーがレビューを削除しようとした場合、ログインページにリダイレクトされることを確認するテスト
    public function test_guest_is_redirected_to_login_when_deleting_review(): void
    {
        $review = Review::factory()->create();

        $response = $this->delete(route('reviews.destroy', $review));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
        ]);
    }
}
