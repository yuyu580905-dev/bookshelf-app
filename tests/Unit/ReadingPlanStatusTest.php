<?php

namespace Tests\Unit;

use App\Enums\ReadingPlanStatus;
use PHPUnit\Framework\TestCase;

class ReadingPlanStatusTest extends TestCase
{
    /**
     * 進行中のラベルとバッジクラスを確認する。
     */
    public function test_in_progress_label_and_badge_class(): void
    {
        $status = ReadingPlanStatus::InProgress;

        $this->assertSame('進行中', $status->label());
        $this->assertSame(
            'bg-blue-100 text-blue-800',
            $status->badgeClass(),
        );
    }

    /**
     * 読了のラベルとバッジクラスを確認する。
     */
    public function test_completed_label_and_badge_class(): void
    {
        $status = ReadingPlanStatus::Completed;

        $this->assertSame('読了', $status->label());
        $this->assertSame(
            'bg-green-100 text-green-800',
            $status->badgeClass(),
        );
    }

    /**
     * 期限切れのラベルとバッジクラスを確認する。
     */
    public function test_expired_label_and_badge_class(): void
    {
        $status = ReadingPlanStatus::Expired;

        $this->assertSame('期限切れ', $status->label());
        $this->assertSame(
            'bg-red-100 text-red-800',
            $status->badgeClass(),
        );
    }
}
