<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCreateTest extends TestCase
{
    use RefreshDatabase;

    // 認証済みユーザーは書籍登録画面を表示できることを確認するテスト
    public function test_authenticated_user_can_view_book_create_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/books/create');

        $response->assertStatus(200);
        $response->assertViewIs('books.create');
    }

    // 書籍登録画面には必要な入力項目と全ジャンルが表示されることを確認するテスト
    public function test_book_create_page_displays_required_fields_and_all_genres(): void
    {
        $user = User::factory()->create();

        $genres = Genre::factory()->count(3)->create();

        $response = $this->actingAs($user)
            ->get('/books/create');

        $response->assertStatus(200);

        $response->assertSee('タイトル');
        $response->assertSee('著者');
        $response->assertSee('ISBN');
        $response->assertSee('出版日');
        $response->assertSee('説明');
        $response->assertSee('画像URL');

        foreach ($genres as $genre) {
            $response->assertSee($genre->name);
        }
    }

    // 未認証ユーザーはログイン画面へリダイレクトされることを確認するテスト
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/books/create');

        $response->assertRedirect('/login');
    }
}
