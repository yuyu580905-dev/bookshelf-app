<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未認証ユーザーはログインページへリダイレクトされる。
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('reports.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * 認証ユーザーの読書レポートが表示される。
     */
    public function test_authenticated_user_can_view_report(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewIs('reports.index');
        $response->assertViewHas('stats');
    }

    /**
     * 基本サマリーが正しく計算されることを確認する。
     */
    public function test_summary_is_calculated_correctly(): void
    {
        $user = User::factory()->create();

        $completedBook = Book::factory()->create();
        $inProgressBook = Book::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $completedBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $inProgressBook->id,
            'rating' => 3,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $completedBook->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $inProgressBook->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();

        $stats = $response->viewData('stats');

        $this->assertSame(2, $stats['summary']['total_reviews']);
        $this->assertSame(1, $stats['summary']['books_read']);
        $this->assertSame(4, $stats['summary']['average_rating']);
    }

    /**
     * レビューがなくても、読書計画が完了している書籍は読了冊数に含まれることを確認する。
     */
    public function test_completed_reading_plan_is_counted_as_read_book_without_review(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();

        $stats = $response->viewData('stats');

        $this->assertSame(0, $stats['summary']['total_reviews']);
        $this->assertSame(1, $stats['summary']['books_read']);
        $this->assertSame(0, $stats['summary']['average_rating']);
    }

    /**
     * 他ユーザーのレビューは集計されない。
     */
    public function test_other_users_reviews_are_not_included(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        Review::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $book->id,
            'rating' => 1,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $stats = $response->viewData('stats');

        $this->assertSame(1, $stats['summary']['total_reviews']);
        $this->assertSame(1, $stats['summary']['books_read']);
        $this->assertEquals(5, $stats['summary']['average_rating']);
    }

    /**
     * 評価分布が1〜5星すべて正しく集計される。
     */
    public function test_rating_distribution_is_calculated_correctly(): void
    {
        $user = User::factory()->create();

        $ratings = [1, 1, 2, 3, 3, 3, 4, 5, 5];

        foreach ($ratings as $rating) {
            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => Book::factory()->create()->id,
                'rating' => $rating,
            ]);
        }

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $stats = $response->viewData('stats');

        $this->assertSame(
            [2, 1, 3, 1, 2],
            $stats['rating_distribution']->all()
        );
    }

    /**
     * 高評価書籍は4星以上のみ表示される。
     */
    public function test_top_rated_books_only_include_four_stars_or_higher(): void
    {
        $user = User::factory()->create();

        $highRatedBook = Book::factory()->create([
            'title' => '高評価書籍',
        ]);

        $lowRatedBook = Book::factory()->create([
            'title' => '低評価書籍',
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $highRatedBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $lowRatedBook->id,
            'rating' => 3,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $stats = $response->viewData('stats');

        $this->assertCount(1, $stats['top_rated_books']);
        $this->assertSame('高評価書籍', $stats['top_rated_books']->first()['title']);
        $this->assertSame(5, $stats['top_rated_books']->first()['rating']);
    }

    /**
     * 同一書籍に複数レビューがある場合、最高評価が採用される。
     */
    public function test_top_rated_book_uses_highest_rating_for_duplicate_reviews(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => '複数レビュー書籍',
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $stats = $response->viewData('stats');

        $this->assertCount(1, $stats['top_rated_books']);
        $this->assertSame(5, $stats['top_rated_books']->first()['rating']);
    }

    /**
     * 高評価書籍は5件まで表示される。
     */
    public function test_top_rated_books_are_limited_to_five(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 6) as $number) {
            $book = Book::factory()->create();

            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => 5,
            ]);
        }

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $stats = $response->viewData('stats');

        $this->assertCount(5, $stats['top_rated_books']);
    }

    /**
     * ジャンル別評価傾向が正しく集計される。
     */
    public function test_genre_ratings_are_calculated_correctly(): void
    {
        $user = User::factory()->create();

        $sf = Genre::factory()->create([
            'name' => 'SF',
        ]);

        $mystery = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $book1 = Book::factory()->create();
        $book1->genres()->attach([$sf->id, $mystery->id]);

        $book2 = Book::factory()->create();
        $book2->genres()->attach($sf->id);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'rating' => 3,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $stats = $response->viewData('stats');

        $genreRatings = $stats['genre_ratings']->keyBy('id');

        $this->assertSame(4, $genreRatings[$sf->id]['average_rating']);
        $this->assertSame(2, $genreRatings[$sf->id]['count']);

        $this->assertSame(5, $genreRatings[$mystery->id]['average_rating']);
        $this->assertSame(1, $genreRatings[$mystery->id]['count']);
    }

    /**
     * ジャンル別評価傾向は5件まで表示される。
     */
    public function test_genre_ratings_are_limited_to_five(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 6) as $number) {
            $genre = Genre::factory()->create();

            $book = Book::factory()->create();
            $book->genres()->attach($genre->id);

            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => 5,
            ]);
        }

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $stats = $response->viewData('stats');

        $this->assertCount(5, $stats['genre_ratings']);
    }
}
