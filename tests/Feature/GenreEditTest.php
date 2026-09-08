<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreEditTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証済みユーザーはジャンル編集ページを表示できる
     */
    public function test_authenticated_user_can_view_genre_edit_page(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $this->actingAs($user)
            ->get(route('genres.edit', $genre))
            ->assertStatus(200)
            ->assertSee('ジャンル編集')
            ->assertSee('ジャンル名')
            ->assertSee('value="ミステリー"', false)
            ->assertSee('更新');
    }

    /**
     * 未認証ユーザーはログイン画面へリダイレクトされる
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $genre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $this->get(route('genres.edit', $genre))
            ->assertRedirect('/login');
    }
}
