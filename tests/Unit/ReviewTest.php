<?php

namespace Tests\Unit;

use App\Models\Review;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    /**
     * userリレーションがBelongsToであることを確認する。
     */
    public function test_user_relationship_is_belongs_to(): void
    {
        $review = new Review;

        $this->assertInstanceOf(
            BelongsTo::class,
            $review->user(),
        );
    }

    /**
     * bookリレーションがBelongsToであることを確認する。
     */
    public function test_book_relationship_is_belongs_to(): void
    {
        $review = new Review;

        $this->assertInstanceOf(
            BelongsTo::class,
            $review->book(),
        );
    }

    /**
     * likedByUsersリレーションがBelongsToManyであることを確認する。
     */
    public function test_liked_by_users_relationship_is_belongs_to_many(): void
    {
        $review = new Review;

        $this->assertInstanceOf(
            BelongsToMany::class,
            $review->likedByUsers(),
        );
    }
}
