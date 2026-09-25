<?php

namespace App\Services;

use App\Enums\ReadingPlanStatus;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * ログインユーザーの読書レポート統計を取得する。
     *
     * @return array<string, mixed>
     */
    public function getStats(User $user): array
    {
        $reviews = $user->reviews()
            ->with('book.genres')
            ->get();

        return [
            'summary' => $this->getSummary($user, $reviews),
            'rating_distribution' => $this->getRatingDistribution($reviews),
            'top_rated_books' => $this->getTopRatedBooks($reviews),
            'genre_ratings' => $this->getGenreRatings($reviews),
        ];
    }

    /**
     * 基本サマリーを取得する。
     *
     * @param  Collection<int, Review>  $reviews
     * @return array<string, int|float>
     */
    private function getSummary(User $user, Collection $reviews): array
    {
        return [
            'total_reviews' => $reviews->count(),
            'books_read' => $user->readingPlans()
                ->where('status', ReadingPlanStatus::Completed)
                ->pluck('book_id')
                ->unique()
                ->count(),
            'average_rating' => $reviews->avg('rating') ?? 0,
        ];
    }

    /**
     * 評価分布を取得する。
     *
     * @param  Collection<int, Review>  $reviews
     * @return Collection<int, int>
     */
    private function getRatingDistribution(Collection $reviews): Collection
    {
        $ratingCounts = $reviews->groupBy('rating')->map->count();

        return collect(range(1, 5))
            ->map(fn (int $rating): int => $ratingCounts->get($rating, 0))
            ->values();
    }

    /**
     * 高評価書籍TOP5を取得する。
     *
     * 同一書籍に複数レビューがある場合は、最高評価を採用する。
     *
     * @param  Collection<int, Review>  $reviews
     * @return Collection<int, array<string, int|string>>
     */
    private function getTopRatedBooks(Collection $reviews): Collection
    {
        return $reviews
            ->filter(fn ($review): bool => $review->rating >= 4)
            ->groupBy('book_id')
            ->map(function (Collection $bookReviews): ?array {
                $highestRatedReview = $bookReviews->sortByDesc('rating')->first();

                if ($highestRatedReview === null || $highestRatedReview->book === null) {
                    return null;
                }

                return [
                    'id' => $highestRatedReview->book->id,
                    'title' => $highestRatedReview->book->title,
                    'author' => $highestRatedReview->book->author,
                    'rating' => $highestRatedReview->rating,
                ];
            })
            ->filter()
            ->sortByDesc('rating')
            ->take(5)
            ->values();
    }

    /**
     * ジャンル別評価傾向TOP5を取得する。
     *
     * 1つの書籍に複数ジャンルが設定されている場合、
     * そのレビューを各ジャンルの集計対象に含める。
     *
     * @param  Collection<int, Review>  $reviews
     * @return Collection<int, array<string, int|float|string>>
     */
    private function getGenreRatings(Collection $reviews): Collection
    {
        return $reviews
            ->flatMap(function ($review): Collection {
                if ($review->book === null) {
                    return collect();
                }

                return $review->book->genres->map(
                    fn ($genre): array => [
                        'id' => $genre->id,
                        'name' => $genre->name,
                        'rating' => $review->rating,
                    ]
                );
            })
            ->groupBy('id')
            ->map(function (Collection $genreReviews): array {
                $firstReview = $genreReviews->first();

                return [
                    'id' => $firstReview['id'],
                    'name' => $firstReview['name'],
                    'average_rating' => $genreReviews->avg('rating'),
                    'count' => $genreReviews->count(),
                ];
            })
            ->sortByDesc('average_rating')
            ->take(5)
            ->values();
    }
}
