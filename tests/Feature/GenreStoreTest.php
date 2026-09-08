<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証済みユーザーはジャンルを登録できる
     */
    public function test_authenticated_user_can_register_genre(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => 'ミステリー',
            ])
            ->assertRedirect(route('genres.index'))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('genres', [
            'name' => 'ミステリー',
        ]);
    }

    /**
     * ジャンル名が未入力の場合はバリデーションエラーになる
     */
    public function test_genre_name_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => '',
            ])
            ->assertSessionHasErrors([
                'name',
            ]);
    }

    /**
     * ジャンル名が重複している場合はバリデーションエラーになる
     */
    public function test_genre_name_must_be_unique(): void
    {
        $user = User::factory()->create();

        Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => 'ミステリー',
            ])
            ->assertSessionHasErrors([
                'name',
            ]);
    }

    /**
     * 未認証ユーザーはログイン画面へリダイレクトされる
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('genres.store'), [
            'name' => 'ミステリー',
        ])
            ->assertRedirect('/login');
    }
}
