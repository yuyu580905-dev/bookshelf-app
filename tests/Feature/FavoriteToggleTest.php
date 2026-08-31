<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteToggleTest extends TestCase
{
    use RefreshDatabase;

    // 認証済みユーザーがお気に入りボタンを押すと、お気に入りに追加されるテスト
    public function test_authenticated_user_can_add_book_to_favorites(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    // 認証済みユーザーがお気に入り登録済みの本を押すと解除されるテスト
    public function test_authenticated_user_can_remove_book_from_favorites(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

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

    // ゲストユーザーがお気に入りボタンを押すとログインページにリダイレクトされるテスト
    public function test_guest_is_redirected_to_login_when_toggling_favorite(): void
    {
        $book = Book::factory()->create();

        $response = $this->post(route('favorites.toggle', $book));

        $response->assertRedirect('/login');

        $this->assertDatabaseMissing('favorites', [
            'book_id' => $book->id,
        ]);
    }
}
