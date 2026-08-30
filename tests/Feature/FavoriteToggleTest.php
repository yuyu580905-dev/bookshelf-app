<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Database\Seeders\BookSeeder;
use Database\Seeders\GenreSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_add_book_to_favorites(): void
    {
        $this->seed([
            UserSeeder::class,
            GenreSeeder::class,
            BookSeeder::class,
        ]);

        $user = User::factory()->create();
        $book = Book::first();

        $response = $this
            ->actingAs($user)
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_authenticated_user_can_remove_book_from_favorites(): void
    {
        $this->seed([
            UserSeeder::class,
            GenreSeeder::class,
            BookSeeder::class,
        ]);

        $user = User::factory()->create();
        $book = Book::first();

        $user->favoriteBooks()->attach($book->id);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_guest_is_redirected_to_login_when_toggling_favorite(): void
    {
        $this->seed([
            UserSeeder::class,
            GenreSeeder::class,
            BookSeeder::class,
        ]);

        $book = Book::first();

        $response = $this->post(route('favorites.toggle', $book));

        $response->assertRedirect('/login');

        $this->assertDatabaseMissing('favorites', [
            'book_id' => $book->id,
        ]);
    }
}
