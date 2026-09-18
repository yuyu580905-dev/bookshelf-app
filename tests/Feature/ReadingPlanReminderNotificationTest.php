<?php

namespace Tests\Feature;

use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanReminderNotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Database通知として設定されている
     */
    public function test_notification_uses_database_channel(): void
    {
        $notification = new ReadingPlanReminderNotification(
            ReadingPlan::factory()->create(),
            'three_days_before'
        );

        $this->assertSame(['database'], $notification->via(new User));
    }

    /**
     * 期限3日前の通知メッセージが正しく生成される
     */
    public function test_notification_generates_three_days_before_message(): void
    {
        $readingPlan = ReadingPlan::factory()->create();
        $user = $readingPlan->user;

        $notification = new ReadingPlanReminderNotification(
            $readingPlan,
            'three_days_before'
        );

        $this->assertSame([
            'reading_plan_id' => $readingPlan->id,
            'book_id' => $readingPlan->book_id,
            'book_title' => $readingPlan->book->title,
            'timing' => 'three_days_before',
            'title' => '読書期限のお知らせ',
            'body' => "「{$readingPlan->book->title}」の読書期限まであと3日です。",
        ], $notification->toArray($user));
    }

    /**
     * 期限当日の通知メッセージが正しく生成される
     */
    public function test_notification_generates_due_date_message(): void
    {
        $readingPlan = ReadingPlan::factory()->create();

        $notification = new ReadingPlanReminderNotification(
            $readingPlan,
            'on_due_date'
        );

        $this->assertSame(
            "「{$readingPlan->book->title}」の読書期限です。",
            $notification->toArray($readingPlan->user)['body']
        );
    }

    /**
     * 期限3日超過の通知メッセージが正しく生成される
     */
    public function test_notification_generates_overdue_message(): void
    {
        $readingPlan = ReadingPlan::factory()->create();

        $notification = new ReadingPlanReminderNotification(
            $readingPlan,
            'three_days_after'
        );

        $this->assertSame(
            "「{$readingPlan->book->title}」の読書期限を3日超過しています。",
            $notification->toArray($readingPlan->user)['body']
        );
    }
}
