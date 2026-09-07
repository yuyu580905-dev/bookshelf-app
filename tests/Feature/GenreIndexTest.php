<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証済みユーザーはジャンル一覧画面を表示できる
     */
    public function test_authenticated_user_can_view_genre_index(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $this->actingAs($user)
            ->get('/genres')
            ->assertStatus(200)
            ->assertSee('小説');
    }

    /**
     * 認証済みユーザーはジャンルごとの書籍数を確認できる
     */
    public function test_book_count_is_displayed_for_each_genre(): void
    {
        $user = User::factory()->create();

        $novel = Genre::factory()->create([
            'name' => '小説',
        ]);

        $comic = Genre::factory()->create([
            'name' => '漫画',
        ]);

        $books = Book::factory()->count(3)->create();

        $novel->books()->attach([
            $books[0]->id,
            $books[1]->id,
        ]);

        $comic->books()->attach([
            $books[2]->id,
        ]);

        $this->actingAs($user)
            ->get('/genres')
            ->assertStatus(200)
            ->assertSee('小説')
            ->assertSee('2冊')
            ->assertSee('漫画')
            ->assertSee('1冊');
    }

    /**
     * 未認証ユーザーはログイン画面へリダイレクトされる
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/genres')
            ->assertRedirect('/login');
    }
}
