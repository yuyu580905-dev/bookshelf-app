<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookEditTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 書籍作成者本人が編集画面にアクセスできる
     */
    public function test_owner_can_view_book_edit_page(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();

        $genres = Genre::factory()->count(2)->create();
        $book->genres()->attach($genres);

        $response = $this->actingAs($user)
            ->get(route('books.edit', $book));

        $response->assertStatus(200);

        $response->assertSee($book->title);
        $response->assertSee($book->author);
        $response->assertSee($book->isbn);
        $response->assertSee($book->published_date);
        $response->assertSee($book->description);
        $response->assertSee($book->image_url);

        foreach ($genres as $genre) {
            $response->assertSee($genre->name);
        }
    }

    /**
     * 他ユーザーが他人の書籍編集画面にアクセスすると403になる
     */
    public function test_other_user_cannot_view_book_edit_page(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)
            ->get(route('books.edit', $book));

        $response->assertForbidden();
    }

    /**
     * ゲストユーザーが書籍編集画面にアクセスするとログインページにリダイレクトされる
     */
    public function test_guest_is_redirected_to_login_when_viewing_book_edit_page(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();

        $response = $this->get(route('books.edit', $book));

        $response->assertRedirect(route('login'));
    }
}
