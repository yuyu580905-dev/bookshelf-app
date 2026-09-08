<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreDeleteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証済みユーザーはジャンルを削除できる（関連する書籍がない場合）
     */
    public function test_authenticated_user_can_delete_genre_without_books(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $this->actingAs($user)
            ->delete(route('genres.destroy', $genre))
            ->assertRedirect(route('genres.index'))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
    }

    /**
     * 認証済みユーザーは関連する書籍があるジャンルを削除できない
     */
    public function test_genre_with_books_cannot_be_deleted(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $book = Book::factory()->create();

        $book->genres()->attach($genre);

        $this->actingAs($user)
            ->delete(route('genres.destroy', $genre))
            ->assertRedirect(route('genres.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'ミステリー',
        ]);
    }

    /**
     * 未認証ユーザーはログイン画面へリダイレクトされる
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $genre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $this->delete(route('genres.destroy', $genre))
            ->assertRedirect('/login');
    }
}
