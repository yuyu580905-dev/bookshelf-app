<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    /**
     * email_verified_atがdatetime型にcastされることを確認する。
     */
    public function test_email_verified_at_is_cast_to_datetime(): void
    {
        $user = new User;

        $user->setAttribute(
            'email_verified_at',
            '2026-10-03 10:30:00',
        );

        $this->assertInstanceOf(
            Carbon::class,
            $user->email_verified_at,
        );

        $this->assertSame(
            '2026-10-03 10:30:00',
            $user->email_verified_at->format('Y-m-d H:i:s'),
        );
    }

    /**
     * passwordがhashed castによってハッシュ化されることを確認する。
     */
    public function test_password_is_hashed(): void
    {
        $password = 'password123';

        $user = new User([
            'password' => $password,
        ]);

        $this->assertNotSame(
            $password,
            $user->password,
        );

        $this->assertTrue(
            Hash::check($password, $user->password),
        );
    }

    /**
     * booksリレーションがHasManyであることを確認する。
     */
    public function test_books_relationship_is_has_many(): void
    {
        $user = new User;

        $this->assertInstanceOf(
            HasMany::class,
            $user->books(),
        );
    }

    /**
     * reviewsリレーションがHasManyであることを確認する。
     */
    public function test_reviews_relationship_is_has_many(): void
    {
        $user = new User;

        $this->assertInstanceOf(
            HasMany::class,
            $user->reviews(),
        );
    }

    /**
     * favoriteBooksリレーションがBelongsToManyであることを確認する。
     */
    public function test_favorite_books_relationship_is_belongs_to_many(): void
    {
        $user = new User;

        $this->assertInstanceOf(
            BelongsToMany::class,
            $user->favoriteBooks(),
        );
    }

    /**
     * likedReviewsリレーションがBelongsToManyであることを確認する。
     */
    public function test_liked_reviews_relationship_is_belongs_to_many(): void
    {
        $user = new User;

        $this->assertInstanceOf(
            BelongsToMany::class,
            $user->likedReviews(),
        );
    }

    /**
     * readingPlansリレーションがHasManyであることを確認する。
     */
    public function test_reading_plans_relationship_is_has_many(): void
    {
        $user = new User;

        $this->assertInstanceOf(
            HasMany::class,
            $user->readingPlans(),
        );
    }
}
