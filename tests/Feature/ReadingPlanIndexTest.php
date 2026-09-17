<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストはログイン画面へリダイレクトされる
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('reading-plans.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * ログインユーザー自身の読書計画だけが表示される
     */
    public function test_authenticated_user_can_see_own_reading_plans_only(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownBook = Book::factory()->create();
        $otherBook = Book::factory()->create();

        $ownPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $ownBook->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $otherPlan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $otherBook->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.index'));

        $response->assertOk()
            ->assertViewIs('reading-plans.index')
            ->assertViewHas('readingPlans', function ($readingPlans) use ($ownPlan, $otherPlan) {
                return $readingPlans->contains($ownPlan)
                    && ! $readingPlans->contains($otherPlan);
            });
    }

    /**
     * statusを指定すると該当する読書計画だけが表示される
     */
    public function test_reading_plans_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();

        $inProgressPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $completedPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.index', [
                'status' => ReadingPlanStatus::Completed->value,
            ]));

        $response->assertOk()
            ->assertViewHas('currentStatus', ReadingPlanStatus::Completed->value)
            ->assertViewHas('readingPlans', function ($readingPlans) use ($completedPlan, $inProgressPlan) {
                return $readingPlans->contains($completedPlan)
                    && ! $readingPlans->contains($inProgressPlan);
            });
    }
}
