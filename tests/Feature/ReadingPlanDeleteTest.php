<?php

namespace Tests\Feature;

use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanDeleteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストは読書計画を削除できず、ログイン画面へリダイレクトされる
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $readingPlan = ReadingPlan::factory()->create();

        $response = $this->delete(
            route('reading-plans.destroy', $readingPlan)
        );

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
        ]);
    }

    /**
     * 所有者は読書計画を削除できる
     */
    public function test_owner_can_delete_reading_plan(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->delete(
                route('reading-plans.destroy', $readingPlan)
            );

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $readingPlan->id,
        ]);
    }

    /**
     * 他ユーザーの読書計画は削除できない
     */
    public function test_non_owner_cannot_delete_reading_plan(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)
            ->delete(
                route('reading-plans.destroy', $readingPlan)
            );

        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $otherUser->id,
        ]);
    }
}
