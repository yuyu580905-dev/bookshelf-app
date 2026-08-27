<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookIndexTest extends TestCase
{
    use RefreshDatabase;

    // ゲストユーザーが書籍一覧ページにアクセスできることを確認するテスト
    public function test_guest_can_view_book_index()
    {
        $this->seed();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('吾輩は猫である');
    }

    // 書籍一覧が10件ずつページネーションされることを確認するテスト
    public function test_books_are_paginated_by_10()
    {
        $this->seed();

        $response = $this->get('/');

        $response->assertStatus(200);

        $this->assertCount(10, $response->viewData('books'));

        $response = $this->get('/?page=2');

        $response->assertStatus(200);

        $this->assertCount(1, $response->viewData('books'));
    }

    // 書籍に紐づくジャンルが表示されることを確認するテスト
    public function test_book_genres_are_displayed()
    {
        $this->seed();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('小説');
        $response->assertSee('ビジネス');
    }
}
