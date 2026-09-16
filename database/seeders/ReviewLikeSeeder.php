<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $reviews = Review::all();

        foreach ($reviews as $review) {
            $reviewCount = rand(0, 3);

            if ($reviewCount === 0) {
                continue;
            }

            $likeUsers = $users
                ->reject(fn ($user) => $user->id === $review->user_id)
                ->random($reviewCount);

            foreach ($likeUsers as $user) {
                $user->likedReviews()->syncWithoutDetaching([$review->id]);
            }
        }
    }
}
