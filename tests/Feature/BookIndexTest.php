<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストユーザーが書籍一覧ページにアクセスできる。
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
     * 書籍一覧が10件ずつページネーションされる。
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
     * 書籍に紐づくジャンルが表示される。
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

    /**
     * キーワードでタイトルを部分一致検索できる。
     */
    public function test_books_can_be_searched_by_title_keyword()
    {
        Book::factory()->create([
            'title' => '吾輩は猫である',
        ]);

        Book::factory()->create([
            'title' => '走れメロス',
        ]);

        $response = $this->get('/?keyword=猫');

        $response->assertStatus(200);
        $response->assertSee('吾輩は猫である');
        $response->assertDontSee('走れメロス');
    }

    /**
     * キーワードで著者名を部分一致検索できる。
     */
    public function test_books_can_be_searched_by_author_keyword()
    {
        Book::factory()->create([
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
        ]);

        Book::factory()->create([
            'title' => '走れメロス',
            'author' => '太宰治',
        ]);

        $response = $this->get('/?keyword=夏目');

        $response->assertStatus(200);
        $response->assertSee('吾輩は猫である');
        $response->assertDontSee('走れメロス');
    }

    /**
     * ジャンルで書籍を絞り込める。
     */
    public function test_books_can_be_filtered_by_genre(): void
    {
        $novel = Genre::factory()->create([
            'name' => '小説',
        ]);

        $business = Genre::factory()->create([
            'name' => 'ビジネス',
        ]);

        $novelBook = Book::factory()->create([
            'title' => '吾輩は猫である',
        ]);

        $businessBook = Book::factory()->create([
            'title' => '7つの習慣',
        ]);

        $novelBook->genres()->attach($novel);
        $businessBook->genres()->attach($business);

        $response = $this->get('/?genre=' . $novel->id);

        $response->assertStatus(200);
        $response->assertSee('吾輩は猫である');
        $response->assertDontSee('7つの習慣');
        $response->assertSee('小説');
        $response->assertSee('ビジネス');
    }

    /**
     * 登録日が新しい順で書籍を並び替えられる。
     */
    public function test_books_can_be_sorted_by_latest(): void
    {
        $oldBook = Book::factory()->create([
            'title' => '古い本',
            'created_at' => now()->subDays(2),
        ]);

        $newBook = Book::factory()->create([
            'title' => '新しい本',
            'created_at' => now(),
        ]);

        $response = $this->get('/?sort=latest');

        $books = $response->viewData('books')->getCollection();

        $this->assertSame($newBook->id, $books->first()->id);
        $this->assertSame($oldBook->id, $books->last()->id);
    }

    /**
     * 登録日が古い順で書籍を並び替えられる。
     */
    public function test_books_can_be_sorted_by_oldest(): void
    {
        $oldBook = Book::factory()->create([
            'title' => '古い本',
            'created_at' => now()->subDays(2),
        ]);

        $newBook = Book::factory()->create([
            'title' => '新しい本',
            'created_at' => now(),
        ]);

        $response = $this->get('/?sort=oldest');

        $books = $response->viewData('books')->getCollection();

        $this->assertSame($oldBook->id, $books->first()->id);
        $this->assertSame($newBook->id, $books->last()->id);
    }

    /**
     * タイトル昇順で書籍を並び替えられる。
     */
    public function test_books_can_be_sorted_by_title(): void
    {
        Book::factory()->create([
            'title' => 'Cの本',
        ]);

        Book::factory()->create([
            'title' => 'Aの本',
        ]);

        Book::factory()->create([
            'title' => 'Bの本',
        ]);

        $response = $this->get('/?sort=title');

        $books = $response->viewData('books')->getCollection();

        $this->assertSame('Aの本', $books->first()->title);
        $this->assertSame('Cの本', $books->last()->title);
    }

    /**
     * 評価順ではレビューなしの書籍が最後に表示される。
     */
    public function test_books_can_be_sorted_by_rating(): void
    {
        $highRatedBook = Book::factory()->create([
            'title' => '高評価本',
        ]);

        $lowRatedBook = Book::factory()->create([
            'title' => '低評価本',
        ]);

        $unreviewedBook = Book::factory()->create([
            'title' => 'レビューなし本',
        ]);

        Review::factory()->create([
            'book_id' => $highRatedBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $lowRatedBook->id,
            'rating' => 3,
        ]);

        $response = $this->get('/?sort=rating');

        $books = $response->viewData('books')->getCollection();

        $this->assertSame('高評価本', $books->get(0)->title);
        $this->assertSame('低評価本', $books->get(1)->title);
        $this->assertSame('レビューなし本', $books->get(2)->title);
    }

    /**
     * ソート指定がない場合は新しい順（登録日）になる。
     */
    public function test_books_are_sorted_by_latest_by_default(): void
    {
        $oldBook = Book::factory()->create([
            'created_at' => now()->subDays(2),
        ]);

        $newBook = Book::factory()->create([
            'created_at' => now(),
        ]);

        $response = $this->get('/');

        $books = $response->viewData('books')->getCollection();

        $this->assertSame($newBook->id, $books->first()->id);
        $this->assertSame($oldBook->id, $books->last()->id);
    }
}
