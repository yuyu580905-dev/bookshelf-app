<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証済みユーザーはジャンル詳細画面を表示できる
     */
    public function test_authenticated_user_can_view_genre_show_page(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $this->actingAs($user)
            ->get(route('genres.show', $genre))
            ->assertStatus(200)
            ->assertSee('小説');
    }

    /**
     * 認証済みユーザーは対象ジャンルの書籍を確認できる
     */
    public function test_books_belonging_to_the_genre_are_displayed(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $book = Book::factory()->create([
            'title' => 'テスト小説',
        ]);

        $genre->books()->attach($book);

        $this->actingAs($user)
            ->get(route('genres.show', $genre))
            ->assertStatus(200)
            ->assertSee('テスト小説');
    }

    /**
     * 認証済みユーザーは対象ジャンル以外の書籍を確認できない
     */
    public function test_books_from_other_genres_are_not_displayed(): void
    {
        $user = User::factory()->create();

        $novel = Genre::factory()->create([
            'name' => '小説',
        ]);

        $comic = Genre::factory()->create([
            'name' => '漫画',
        ]);

        $novelBook = Book::factory()->create([
            'title' => 'テスト小説',
        ]);

        $comicBook = Book::factory()->create([
            'title' => 'テスト漫画',
        ]);

        $novel->books()->attach($novelBook);
        $comic->books()->attach($comicBook);

        $this->actingAs($user)
            ->get(route('genres.show', $novel))
            ->assertStatus(200)
            ->assertSee('テスト小説')
            ->assertDontSee('テスト漫画');
    }

    /**
     * 未認証ユーザーはログイン画面へリダイレクトされる
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $genre = Genre::factory()->create();

        $this->get(route('genres.show', $genre))
            ->assertRedirect('/login');
    }
}
