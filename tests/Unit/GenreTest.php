<?php

namespace Tests\Unit;

use App\Models\Genre;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tests\TestCase;

class GenreTest extends TestCase
{
    /**
     * booksリレーションがBelongsToManyであることを確認する。
     */
    public function test_books_relationship_is_belongs_to_many(): void
    {
        $genre = new Genre;

        $this->assertInstanceOf(
            BelongsToMany::class,
            $genre->books(),
        );
    }
}
