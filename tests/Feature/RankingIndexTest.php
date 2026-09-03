<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストユーザーがランキングページにアクセスできる
     */
    public function test_guest_can_access_ranking_page(): void
    {
        $response = $this->get('/ranking');

        $response->assertStatus(200);
    }

    /**
     * 書籍が平均評価の降順で表示される
     */
    public function test_books_are_displayed_in_descending_order_of_average_rating(): void
    {
        $user = User::factory()->create();

        $lowRatedBook = Book::factory()->create([
            'title' => '低評価書籍',
        ]);

        $highRatedBook = Book::factory()->create([
            'title' => '高評価書籍',
        ]);

        Review::factory()->create([
            'book_id' => $lowRatedBook->id,
            'user_id' => $user->id,
            'rating' => 2,
        ]);

        Review::factory()->create([
            'book_id' => $highRatedBook->id,
            'user_id' => $user->id,
            'rating' => 5,
        ]);

        $response = $this->get('/ranking');

        $response->assertStatus(200);

        $response->assertSeeInOrder([
            '高評価書籍',
            '低評価書籍',
        ]);
    }

    /**
     * レビューがない書籍は表示されない
     */
    public function test_books_without_reviews_are_not_displayed(): void
    {
        $reviewedBook = Book::factory()->create([
            'title' => 'レビューあり書籍',
        ]);

        Book::factory()->create([
            'title' => 'レビューなし書籍',
        ]);

        Review::factory()->create([
            'book_id' => $reviewedBook->id,
            'rating' => 5,
        ]);

        $response = $this->get('/ranking');

        $response->assertStatus(200);
        $response->assertSee('レビューあり書籍');
        $response->assertDontSee('レビューなし書籍');
    }

    /**
     * ランキングページには上位10件の書籍のみが表示される
     */
    public function test_only_top_10_books_are_displayed(): void
    {
        $user = User::factory()->create();

        for ($rating = 1; $rating <= 10; $rating++) {
            $book = Book::factory()->create([
                'title' => "ランキング{$rating}位",
            ]);

            Review::factory()->create([
                'book_id' => $book->id,
                'user_id' => $user->id,
                'rating' => $rating,
            ]);
        }

        $eleventhBook = Book::factory()->create([
            'title' => 'ランキング11位',
        ]);

        Review::factory()->create([
            'book_id' => $eleventhBook->id,
            'user_id' => $user->id,
            'rating' => 1,
        ]);

        $response = $this->get('/ranking');

        $response->assertStatus(200);

        for ($rating = 1; $rating <= 10; $rating++) {
            $response->assertSee("ランキング{$rating}位");
        }

        $response->assertDontSee('ランキング11位');
    }
}
