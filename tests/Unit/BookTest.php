<?php

namespace Tests\Unit;

use App\Models\Book;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookTest extends TestCase
{
    /**
     * published_dateがdate型にcastされることを確認する。
     */
    public function test_published_date_is_cast_to_date(): void
    {
        $book = new Book([
            'published_date' => '2026-10-03',
        ]);

        $this->assertInstanceOf(
            Carbon::class,
            $book->published_date,
        );

        $this->assertSame(
            '2026-10-03',
            $book->published_date->toDateString(),
        );
    }

    /**
     * userリレーションがBelongsToであることを確認する。
     */
    public function test_user_relationship_is_belongs_to(): void
    {
        $book = new Book;

        $this->assertInstanceOf(
            BelongsTo::class,
            $book->user(),
        );
    }

    /**
     * reviewsリレーションがHasManyであることを確認する。
     */
    public function test_reviews_relationship_is_has_many(): void
    {
        $book = new Book;

        $this->assertInstanceOf(
            HasMany::class,
            $book->reviews(),
        );
    }

    /**
     * favoritedByUsersリレーションがBelongsToManyであることを確認する。
     */
    public function test_favorited_by_users_relationship_is_belongs_to_many(): void
    {
        $book = new Book;

        $this->assertInstanceOf(
            BelongsToMany::class,
            $book->favoritedByUsers(),
        );
    }

    /**
     * genresリレーションがBelongsToManyであることを確認する。
     */
    public function test_genres_relationship_is_belongs_to_many(): void
    {
        $book = new Book;

        $this->assertInstanceOf(
            BelongsToMany::class,
            $book->genres(),
        );
    }

    /**
     * readingPlansリレーションがHasManyであることを確認する。
     */
    public function test_reading_plans_relationship_is_has_many(): void
    {
        $book = new Book;

        $this->assertInstanceOf(
            HasMany::class,
            $book->readingPlans(),
        );
    }
}
