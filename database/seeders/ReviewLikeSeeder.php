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
        $users = User::all()->keyBy('email');

        $reviews = Review::with(['book', 'user'])
            ->get()
            ->groupBy(fn ($review) => $review->book->isbn)
            ->map(fn ($bookReviews) => $bookReviews->keyBy(
                fn ($review) => $review->user->email
            ));

        $likes = [
            // 吾輩は猫である
            [
                'review' => ['9784101010014', 'yamada@example.com'],
                'users' => ['suzuki@example.com', 'tanaka@example.com'],
            ],
            [
                'review' => ['9784101010014', 'suzuki@example.com'],
                'users' => ['takahashi@example.com'],
            ],
            [
                'review' => ['9784101010014', 'tanaka@example.com'],
                'users' => ['yamada@example.com', 'sato@example.com'],
            ],

            // 人を動かす
            [
                'review' => ['9784422100524', 'sato@example.com'],
                'users' => ['yamada@example.com', 'takahashi@example.com'],
            ],
            [
                'review' => ['9784422100524', 'takahashi@example.com'],
                'users' => ['suzuki@example.com'],
            ],
            [
                'review' => ['9784422100524', 'yamada@example.com'],
                'users' => ['tanaka@example.com', 'sato@example.com'],
            ],

            // リーダブルコード
            [
                'review' => ['9784873115658', 'suzuki@example.com'],
                'users' => ['yamada@example.com', 'sato@example.com'],
            ],
            [
                'review' => ['9784873115658', 'tanaka@example.com'],
                'users' => ['takahashi@example.com'],
            ],
            [
                'review' => ['9784873115658', 'sato@example.com'],
                'users' => ['suzuki@example.com', 'tanaka@example.com'],
            ],

            // 7つの習慣
            [
                'review' => ['9784863940246', 'takahashi@example.com'],
                'users' => ['yamada@example.com', 'sato@example.com'],
            ],
            [
                'review' => ['9784863940246', 'yamada@example.com'],
                'users' => ['suzuki@example.com'],
            ],
            [
                'review' => ['9784863940246', 'suzuki@example.com'],
                'users' => ['tanaka@example.com', 'takahashi@example.com'],
            ],

            // 坊っちゃん
            [
                'review' => ['9784101010021', 'tanaka@example.com'],
                'users' => ['sato@example.com'],
            ],
            [
                'review' => ['9784101010021', 'sato@example.com'],
                'users' => ['yamada@example.com', 'takahashi@example.com'],
            ],
            [
                'review' => ['9784101010021', 'takahashi@example.com'],
                'users' => [],
            ],

            // サピエンス全史
            [
                'review' => ['9784309226712', 'yamada@example.com'],
                'users' => ['suzuki@example.com', 'tanaka@example.com'],
            ],
            [
                'review' => ['9784309226712', 'suzuki@example.com'],
                'users' => ['sato@example.com'],
            ],
            [
                'review' => ['9784309226712', 'tanaka@example.com'],
                'users' => ['yamada@example.com', 'takahashi@example.com'],
            ],

            // Clean Code
            [
                'review' => ['9784048930598', 'sato@example.com'],
                'users' => ['yamada@example.com', 'tanaka@example.com'],
            ],
            [
                'review' => ['9784048930598', 'takahashi@example.com'],
                'users' => ['suzuki@example.com'],
            ],
            [
                'review' => ['9784048930598', 'yamada@example.com'],
                'users' => ['sato@example.com', 'takahashi@example.com'],
            ],

            // 嫌われる勇気
            [
                'review' => ['9784478025819', 'suzuki@example.com'],
                'users' => ['yamada@example.com', 'tanaka@example.com'],
            ],
            [
                'review' => ['9784478025819', 'tanaka@example.com'],
                'users' => ['sato@example.com'],
            ],
            [
                'review' => ['9784478025819', 'sato@example.com'],
                'users' => ['suzuki@example.com', 'takahashi@example.com'],
            ],

            // 火花
            [
                'review' => ['9784163902302', 'takahashi@example.com'],
                'users' => ['yamada@example.com'],
            ],
            [
                'review' => ['9784163902302', 'yamada@example.com'],
                'users' => ['suzuki@example.com', 'sato@example.com'],
            ],
            [
                'review' => ['9784163902302', 'suzuki@example.com'],
                'users' => [],
            ],

            // FACTFULNESS
            [
                'review' => ['9784822289607', 'tanaka@example.com'],
                'users' => ['yamada@example.com', 'sato@example.com'],
            ],
            [
                'review' => ['9784822289607', 'sato@example.com'],
                'users' => ['takahashi@example.com'],
            ],
            [
                'review' => ['9784822289607', 'takahashi@example.com'],
                'users' => ['suzuki@example.com', 'tanaka@example.com'],
            ],

            // コンテナ物語
            [
                'review' => ['9784822251468', 'yamada@example.com'],
                'users' => ['sato@example.com'],
            ],
            [
                'review' => ['9784822251468', 'takahashi@example.com'],
                'users' => ['yamada@example.com', 'suzuki@example.com'],
            ],
        ];

        foreach ($likes as $like) {
            $review = $reviews[$like['review'][0]][$like['review'][1]];

            foreach ($like['users'] as $email) {
                $user = $users[$email];

                if ($user->id !== $review->user_id) {
                    $user->likedReviews()->syncWithoutDetaching([$review->id]);
                }
            }
        }
    }
}
