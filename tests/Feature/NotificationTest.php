<?php

namespace Tests\Feature;

use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証ユーザーの通知一覧を表示できる
     */
    public function test_authenticated_user_can_view_notifications(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        Notification::send(
            $user,
            new ReadingPlanReminderNotification(
                $readingPlan,
                'three_days_before'
            )
        );

        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response
            ->assertOk()
            ->assertViewIs('notifications.index')
            ->assertViewHas('notifications', function ($notifications): bool {
                return $notifications->count() === 1;
            });
    }

    /**
     * ゲストは通知一覧にアクセスできず、ログイン画面へリダイレクトされる
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('notifications.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * 自分の未読通知を既読にできる
     */
    public function test_authenticated_user_can_mark_own_notification_as_read(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        Notification::send(
            $user,
            new ReadingPlanReminderNotification(
                $readingPlan,
                'on_due_date'
            )
        );

        $notification = $user->notifications()->first();

        $this->assertNull($notification->read_at);

        $response = $this->actingAs($user)
            ->post(route('notifications.read', $notification->id));

        $response
            ->assertRedirect(route('notifications.index'));

        $this->assertNotNull(
            $user->notifications()->find($notification->id)->read_at
        );
    }

    /**
     * 他ユーザーの通知は既読にできない
     */
    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $owner->id,
        ]);

        Notification::send(
            $owner,
            new ReadingPlanReminderNotification(
                $readingPlan,
                'three_days_before'
            )
        );

        $notification = $owner->notifications()->first();

        $response = $this->actingAs($otherUser)
            ->post(route('notifications.read', $notification->id));

        $response->assertNotFound();

        $this->assertNull(
            $owner->notifications()->find($notification->id)->read_at
        );
    }
}
