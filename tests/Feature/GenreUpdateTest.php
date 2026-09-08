<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証済みユーザーはジャンルを更新できる
     */
    public function test_authenticated_user_can_update_genre(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => 'SF',
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'SF',
        ]);
    }

    /**
     * ジャンル名が未入力の場合はバリデーションエラーになる
     */
    public function test_genre_name_is_required(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '',
            ])
            ->assertSessionHasErrors([
                'name',
            ]);
    }

    /**
     * ジャンル名が文字列でない場合はバリデーションエラーになる
     */
    public function test_genre_name_must_be_string(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => ['SF'],
            ])
            ->assertSessionHasErrors([
                'name',
            ]);
    }

    /**
     * ジャンル名が255文字を超える場合はバリデーションエラーになる
     */
    public function test_genre_name_must_not_exceed_255_characters(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => str_repeat('あ', 256),
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

        $genre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        Genre::factory()->create([
            'name' => 'SF',
        ]);

        $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => 'SF',
            ])
            ->assertSessionHasErrors([
                'name',
            ]);
    }

    /**
     * ジャンル名が変更されない場合はバリデーションエラーにならない（自身のレコードを除外してユニークチェックする）
     */
    public function test_genre_name_can_remain_the_same(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => 'ミステリー',
            ])
            ->assertSessionDoesntHaveErrors();

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

        $this->put(route('genres.update', $genre), [
            'name' => 'SF',
        ])
            ->assertRedirect('/login');
    }
}
