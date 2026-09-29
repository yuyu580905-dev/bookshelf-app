<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Support\Facades\Auth;

class ReviewLikeController extends Controller
{
    public function reviewLike(Review $review)
    {
        $user = Auth::user();

        if ($user->likedReviews()->where('review_id', $review->id)->exists()) {
            $user->likedReviews()->detach($review->id);
        } else {
            $user->likedReviews()->attach($review->id);
        }

        return redirect()->route('books.show', $review->book);
    }
}
