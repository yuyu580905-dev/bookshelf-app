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
        Carbon::setTestNow('2026-09-19 20:00:00');

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
        Carbon::setTestNow('2026-09-19 20:00:00');

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
     * Completed（読了済み）の読書計画は期限切れに変更しない。
     */
    public function test_completed_reading_plan_is_not_marked_as_expired(): void
    {
        Carbon::setTestNow('2026-09-19 20:00:00');

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
     * 期日の3日前にリマインダー通知をDatabaseChannelへ送信する。
     */
    public function test_sends_three_days_before_reminder(): void
    {
        Carbon::setTestNow('2026-09-19 20:00:00');

        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => Carbon::today()->addDays(3),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
            'type' => ReadingPlanReminderNotification::class,
        ]);

        $notification = $user->notifications()->first();

        $this->assertSame(
            'three_days_before',
            $notification->data['timing']
        );

        $this->assertSame(
            $readingPlan->id,
            $notification->data['reading_plan_id']
        );
    }

    /**
     * 期日当日にリマインダー通知をDatabaseChannelへ送信する。
     */
    public function test_sends_on_due_date_reminder(): void
    {
        Carbon::setTestNow('2026-09-19 20:00:00');

        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => Carbon::today(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
            'type' => ReadingPlanReminderNotification::class,
        ]);

        $notification = $user->notifications()->first();

        $this->assertSame(
            'on_due_date',
            $notification->data['timing']
        );

        $this->assertSame(
            $readingPlan->id,
            $notification->data['reading_plan_id']
        );
    }

    /**
     * 期日の3日後にリマインダー通知をDatabaseChannelへ送信する。
     */
    public function test_sends_three_days_after_reminder(): void
    {
        Carbon::setTestNow('2026-09-19 20:00:00');

        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => Carbon::today()->subDays(3),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
            'type' => ReadingPlanReminderNotification::class,
        ]);

        $notification = $user->notifications()->first();

        $this->assertSame(
            'three_days_after',
            $notification->data['timing']
        );

        $this->assertSame(
            $readingPlan->id,
            $notification->data['reading_plan_id']
        );
    }

    /**
     * リマインダー対象日以外の読書計画には通知を送信しない。
     */
    public function test_does_not_send_reminder_for_non_reminder_date(): void
    {
        Carbon::setTestNow('2026-09-19 20:00:00');

        $user = User::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => Carbon::today()->addDays(2),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        $this->assertDatabaseCount('notifications', 0);
    }

    /**
     * 同じ読書計画・同じ通知タイミングの重複通知を防止する。
     */
    public function test_does_not_send_duplicate_reminder(): void
    {
        Carbon::setTestNow('2026-09-19 20:00:00');

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
     * 通知済みの読書計画があっても後続の読書計画にリマインダー通知を送信する。
     */
    public function test_sends_reminder_to_subsequent_reading_plan_after_duplicate(): void
    {
        Carbon::setTestNow('2026-09-19 20:00:00');

        $user = User::factory()->create();

        $notifiedReadingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => Carbon::today(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $newReadingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => Carbon::today(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $user->notify(
            new ReadingPlanReminderNotification(
                $notifiedReadingPlan,
                'on_due_date',
            )
        );

        $this->assertDatabaseCount('notifications', 1);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        $this->assertSame(
            2,
            $user->notifications()
                ->where('data->timing', 'on_due_date')
                ->count()
        );

        $this->assertSame(
            1,
            $user->notifications()
                ->where('data->reading_plan_id', $newReadingPlan->id)
                ->where('data->timing', 'on_due_date')
                ->count()
        );
    }

    /**
     * 読了済みの読書計画にはリマインダー通知を送信しない。
     */
    public function test_does_not_send_reminder_for_completed_reading_plan(): void
    {
        Carbon::setTestNow('2026-09-19 20:00:00');
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
