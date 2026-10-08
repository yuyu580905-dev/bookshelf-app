<?php

namespace Tests\Feature;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 書籍詳細ページが正常に表示され、書籍タイトルが含まれる。
     */
    public function test_book_show_page_displays_book_title(): void
    {
        $book = Book::factory()->create();

        $response = $this->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertSee($book->title);
    }
}
