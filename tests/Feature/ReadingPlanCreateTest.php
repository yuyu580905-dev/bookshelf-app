<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanCreateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストは読書計画作成画面にアクセスできず、ログイン画面へリダイレクトされる。
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('reading-plans.create'));

        $response->assertRedirect(route('login'));
    }

    /**
     * 認証ユーザーは読書計画作成画面を表示できる。
     */
    public function test_authenticated_user_can_see_create_form(): void
    {
        $user = User::factory()->create();
        $books = Book::factory()->count(3)->create();

        $response = $this->actingAs($user)
            ->get(route('reading-plans.create'));

        $response->assertOk()
            ->assertViewIs('reading-plans.create')
            ->assertViewHas('books', function ($viewBooks) use ($books) {
                return $viewBooks->count() === 3
                    && $viewBooks->pluck('id')->sort()->values()
                        ->all() === $books->pluck('id')->sort()->values()->all();
            });
    }
}
