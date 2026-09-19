<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ProcessReadingPlans extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'reading-plans:process';

    /**
     * The console command description.
     */
    protected $description = '読書計画の期限切れ更新とリマインダー通知を処理する';

    /**
     * 読書計画の期限切れ更新とリマインダー通知を処理する。
     */
    public function handle(): int
    {
        $today = Carbon::today();

        $this->expireReadingPlans($today);
        $this->sendReminderNotifications($today);

        return self::SUCCESS;
    }

    /**
     * 期限を過ぎた読書計画を期限切れに変更する。
     */
    private function expireReadingPlans(Carbon $today): void
    {
        ReadingPlan::query()
            ->where('status', ReadingPlanStatus::InProgress->value)
            ->whereDate('target_date', '<', $today)
            ->update([
                'status' => ReadingPlanStatus::Expired->value,
            ]);
    }

    /**
     * 条件を満たす読書計画にリマインダー通知を送信する。
     */
    private function sendReminderNotifications(Carbon $today): void
    {
        $timings = [
            'three_days_before' => $today->copy()->addDays(3),
            'on_due_date' => $today->copy(),
            'three_days_after' => $today->copy()->subDays(3),
        ];

        collect($timings)->each(
            function (Carbon $targetDate, string $timing): void {
                ReadingPlan::query()
                    ->with(['user', 'book'])
                    ->whereDate('target_date', $targetDate)
                    ->where('status', '!=', ReadingPlanStatus::Completed->value)
                    ->get()
                    ->each(
                        fn (ReadingPlan $readingPlan): bool => $this->sendNotification(
                            $readingPlan,
                            $timing,
                        )
                    );
            }
        );
    }

    /**
     * 未送信のリマインダー通知を送信する。
     */
    private function sendNotification(
        ReadingPlan $readingPlan,
        string $timing,
    ): bool {
        $alreadySent = $readingPlan->user
            ->notifications()
            ->where('type', ReadingPlanReminderNotification::class)
            ->where('data->reading_plan_id', $readingPlan->id)
            ->where('data->timing', $timing)
            ->exists();

        if ($alreadySent) {
            return false;
        }

        $readingPlan->user->notify(
            new ReadingPlanReminderNotification(
                $readingPlan,
                $timing,
            )
        );

        return true;
    }
}
