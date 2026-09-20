<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProcessReadingPlansTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テスト終了後にテスト時刻をリセットする。
     */
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * 期限を過ぎた読書計画を期限切れに変更する。
     */
    public function test_expired_reading_plan_is_marked_as_expired(): void
    {
        Carbon::setTestNow('2026-09-19 09:00:00');

        $readingPlan = ReadingPlan::factory()->create([
            'target_date' => Carbon::yesterday(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::Expired->value,
        ]);
    }

    /**
     * 期日当日の読書計画は期限切れに変更しない。
     */
    public function test_reading_plan_on_target_date_is_not_marked_as_expired(): void
    {
        Carbon::setTestNow('2026-09-19 09:00:00');

        $readingPlan = ReadingPlan::factory()->create([
            'target_date' => Carbon::today(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    /**
     * 読了済みの読書計画は期限切れに変更しない。
     */
    public function test_completed_reading_plan_is_not_marked_as_expired(): void
    {
        Carbon::setTestNow('2026-09-19 09:00:00');

        $readingPlan = ReadingPlan::factory()->create([
            'target_date' => Carbon::yesterday(),
            'status' => ReadingPlanStatus::Completed,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::Completed->value,
        ]);
    }

    /**
     * 期日の3日前にリマインダー通知を送信する。
     */
    public function test_sends_three_days_before_reminder(): void
    {
        Carbon::setTestNow('2026-09-19 09:00:00');
        Notification::fake();

        $user = User::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => Carbon::today()->addDays(3),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        Notification::assertSentTo(
            $user,
            ReadingPlanReminderNotification::class,
            fn(ReadingPlanReminderNotification $notification): bool => $notification->toArray($user)['timing'] === 'three_days_before'
        );
    }

    /**
     * 期日にリマインダー通知を送信する。
     */
    public function test_sends_on_due_date_reminder(): void
    {
        Carbon::setTestNow('2026-09-19 09:00:00');
        Notification::fake();

        $user = User::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => Carbon::today(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        Notification::assertSentTo(
            $user,
            ReadingPlanReminderNotification::class,
            fn(ReadingPlanReminderNotification $notification): bool => $notification->toArray($user)['timing'] === 'on_due_date'
        );
    }

    /**
     * 期日の3日後にリマインダー通知を送信する。
     */
    public function test_sends_three_days_after_reminder(): void
    {
        Carbon::setTestNow('2026-09-19 09:00:00');
        Notification::fake();

        $user = User::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => Carbon::today()->subDays(3),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        Notification::assertSentTo(
            $user,
            ReadingPlanReminderNotification::class,
            fn(ReadingPlanReminderNotification $notification): bool => $notification->toArray($user)['timing'] === 'three_days_after'
        );
    }

    /**
     * 同じ読書計画・同じ通知タイミングの重複通知を防止する。
     */
    public function test_does_not_send_duplicate_reminder(): void
    {
        Carbon::setTestNow('2026-09-19 09:00:00');

        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => Carbon::today()->addDays(3),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $user->notify(
            new ReadingPlanReminderNotification(
                $readingPlan,
                'three_days_before',
            )
        );

        $this->assertDatabaseCount('notifications', 1);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        $this->assertDatabaseCount('notifications', 1);
    }

    /**
     * 読了済みの読書計画にはリマインダー通知を送信しない。
     */
    public function test_does_not_send_reminder_for_completed_reading_plan(): void
    {
        Carbon::setTestNow('2026-09-19 09:00:00');
        Notification::fake();

        $user = User::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => Carbon::today(),
            'status' => ReadingPlanStatus::Completed,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }
}
