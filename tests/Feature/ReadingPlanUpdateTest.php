<?php

namespace Tests\Feature;

use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストは読書計画を更新できず、ログイン画面へリダイレクトされる。
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $readingPlan = ReadingPlan::factory()->create([
            'target_date' => '2026-10-01',
        ]);

        $response = $this->put(
            route('reading-plans.update', $readingPlan),
            [
                'target_date' => '2026-11-01',
            ]
        );

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => '2026-10-01',
        ]);
    }

    /**
     * 所有者は読書計画の期日を更新できる。
     */
    public function test_owner_can_update_reading_plan(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-10-01',
        ]);

        $response = $this->actingAs($user)
            ->put(
                route('reading-plans.update', $readingPlan),
                [
                    'target_date' => '2026-11-01',
                ]
            );

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $user->id,
            'target_date' => '2026-11-01',
        ]);
    }

    /**
     * 他ユーザーの読書計画は更新できない。
     */
    public function test_non_owner_cannot_update_reading_plan(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
            'target_date' => '2026-10-01',
        ]);

        $response = $this->actingAs($user)
            ->put(
                route('reading-plans.update', $readingPlan),
                [
                    'target_date' => '2026-11-01',
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => '2026-10-01',
        ]);
    }

    /**
     * target_dateが未入力の場合はバリデーションエラーになる。
     */
    public function test_target_date_is_required(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-10-01',
        ]);

        $response = $this->actingAs($user)
            ->put(
                route('reading-plans.update', $readingPlan),
                []
            );

        $response->assertSessionHasErrors('target_date');

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => '2026-10-01',
        ]);
    }

    /**
     * target_dateが日付形式でない場合はバリデーションエラーになる。
     */
    public function test_target_date_must_be_a_valid_date(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-10-01',
        ]);

        $response = $this->actingAs($user)
            ->put(
                route('reading-plans.update', $readingPlan),
                [
                    'target_date' => 'invalid-date',
                ]
            );

        $response->assertSessionHasErrors('target_date');

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => '2026-10-01',
        ]);
    }
}
