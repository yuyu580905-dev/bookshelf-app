<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteToggleTest extends TestCase
{
    use RefreshDatabase;

    // 認証済みユーザーがお気に入りを追加・解除・再追加できるテスト
    public function test_authenticated_user_can_toggle_book_favorite(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // ① お気に入りに追加
        $response = $this
            ->actingAs($user)
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // ② お気に入りを解除
        $response = $this
            ->actingAs($user)
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // ③ お気に入りに再追加
        $response = $this
            ->actingAs($user)
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('favorites', [
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
