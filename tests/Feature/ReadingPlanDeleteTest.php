<?php

namespace Tests\Feature;

use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReadingPlanDeleteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストは読書計画を削除できず、ログイン画面へリダイレクトされる。
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
     * 所有者は読書計画を削除できる。
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
     * 読書計画を削除すると関連するリマインダー通知も削除される。
     */
    public function test_owner_can_delete_reading_plan_with_related_notifications(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $notification = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\ReadingPlanReminderNotification',
            'data' => [
                'reading_plan_id' => $readingPlan->id,
                'book_id' => $readingPlan->book_id,
                'book_title' => $readingPlan->book->title,
                'timing' => 'on_due_date',
                'title' => '読書計画の期限日です',
                'body' => '読書計画の期限日です。',
            ],
        ]);

        $response = $this->actingAs($user)
            ->delete(
                route('reading-plans.destroy', $readingPlan)
            );

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $readingPlan->id,
        ]);

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }

    /**
     * 他ユーザーの読書計画を削除しようとすると403になる。
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
