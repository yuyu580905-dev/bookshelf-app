<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストユーザーが書籍一覧ページにアクセスできる
     */
    public function test_guest_can_view_book_index()
    {
        Book::factory()->create([
            'title' => '吾輩は猫である',
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('吾輩は猫である');
    }

    /**
     * 書籍一覧が10件ずつページネーションされる
     */
    public function test_books_are_paginated_by_10()
    {
        Book::factory()->count(11)->create();

        $response = $this->get('/');

        $response->assertStatus(200);

        $this->assertCount(10, $response->viewData('books'));

        $response = $this->get('/?page=2');

        $response->assertStatus(200);

        $this->assertCount(1, $response->viewData('books'));
    }

    /**
     * 書籍に紐づくジャンルが表示される
     */
    public function test_book_genres_are_displayed()
    {
        $novel = Genre::factory()->create([
            'name' => '小説',
        ]);

        $business = Genre::factory()->create([
            'name' => 'ビジネス',
        ]);

        $book = Book::factory()->create();

        $book->genres()->attach([
            $novel->id,
            $business->id,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('小説');
        $response->assertSee('ビジネス');
    }
}
