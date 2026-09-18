<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanReminderNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly ReadingPlan $readingPlan,
        private readonly string $timing,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reading_plan_id' => $this->readingPlan->id,
            'book_id' => $this->readingPlan->book_id,
            'book_title' => $this->readingPlan->book->title,
            'timing' => $this->timing,
            'title' => $this->title(),
            'body' => $this->body(),
        ];
    }

    /**
     * 通知タイトルを取得する。
     */
    private function title(): string
    {
        return match ($this->timing) {
            'three_days_before' => '読書期限のお知らせ',
            'on_due_date' => '読書期限です',
            'three_days_after' => '読書計画の期限超過',
            default => '読書計画のお知らせ',
        };
    }

    /**
     * 通知本文を取得する。
     */
    private function body(): string
    {
        return match ($this->timing) {
            'three_days_before' => "「{$this->readingPlan->book->title}」の読書期限まであと3日です。",
            'on_due_date' => "「{$this->readingPlan->book->title}」の読書期限です。",
            'three_days_after' => "「{$this->readingPlan->book->title}」の読書期限を3日超過しています。",
            default => "「{$this->readingPlan->book->title}」の読書期限に関する通知です。",
        };
    }
}
