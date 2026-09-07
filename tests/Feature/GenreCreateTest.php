<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreCreateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証済みユーザーはジャンル登録画面を表示できる
     */
    public function test_authenticated_user_can_view_genre_create_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('genres.create'))
            ->assertStatus(200)
            ->assertSee('ジャンル登録')
            ->assertSee('ジャンル名')
            ->assertSee('登録');
    }

    /**
     * 未認証ユーザーはログイン画面へリダイレクトされる
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('genres.create'))
            ->assertRedirect('/login');
    }
}
