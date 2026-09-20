<?php

namespace Tests\Feature;

use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanEditTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストは読書計画編集画面にアクセスできず、ログイン画面へリダイレクトされる。
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $readingPlan = ReadingPlan::factory()->create();

        $response = $this->get(
            route('reading-plans.edit', $readingPlan)
        );

        $response->assertRedirect(route('login'));
    }

    /**
     * 所有者は読書計画編集画面を表示できる。
     */
    public function test_owner_can_see_edit_form(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-10-01',
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.edit', $readingPlan));

        $response->assertOk()
            ->assertViewIs('reading-plans.edit')
            ->assertViewHas('readingPlan', function ($viewReadingPlan) use ($readingPlan) {
                return $viewReadingPlan->is($readingPlan);
            })
            ->assertSee('2026-10-01');
    }

    /**
     * 他ユーザーの読書計画は編集画面を表示できない。
     */
    public function test_non_owner_cannot_see_edit_form(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.edit', $readingPlan));

        $response->assertForbidden();
    }
}
