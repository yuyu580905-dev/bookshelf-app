<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    // ログアウト後にログイン画面にリダイレクトされることを確認するテスト
    public function test_users_can_logout()
    {
        $user = User::factory()->create();

        // ユーザーとしてログイン→ログアウト
        $response = $this->actingAs($user)->post('/logout');

        // ログアウト後、ユーザーが認証されていないことを確認
        $this->assertGuest();

        // ログアウト後、ログイン画面にリダイレクトされることを確認
        $response->assertRedirect('/');
        $this->get('/')->assertRedirect('/login');
    }
}
