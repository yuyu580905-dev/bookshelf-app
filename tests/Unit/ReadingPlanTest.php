<?php

namespace Tests\Unit;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
{
    /**
     * statusがReadingPlanStatus Enumにcastされることを確認する。
     */
    public function test_status_is_cast_to_reading_plan_status_enum(): void
    {
        $readingPlan = new ReadingPlan([
            'status' => ReadingPlanStatus::InProgress->value,
        ]);

        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $readingPlan->status,
        );
    }

    /**
     * target_dateがdate型にcastされることを確認する。
     */
    public function test_target_date_is_cast_to_date(): void
    {
        $readingPlan = new ReadingPlan([
            'target_date' => '2026-10-03',
        ]);

        $this->assertInstanceOf(
            Carbon::class,
            $readingPlan->target_date,
        );

        $this->assertSame(
            '2026-10-03',
            $readingPlan->target_date->toDateString(),
        );
    }

    /**
     * completed_atがdatetime型にcastされることを確認する。
     */
    public function test_completed_at_is_cast_to_datetime(): void
    {
        $readingPlan = new ReadingPlan([
            'completed_at' => '2026-10-03 10:30:00',
        ]);

        $this->assertInstanceOf(
            Carbon::class,
            $readingPlan->completed_at,
        );

        $this->assertSame(
            '2026-10-03 10:30:00',
            $readingPlan->completed_at->format('Y-m-d H:i:s'),
        );
    }

    /**
     * userリレーションがBelongsToであることを確認する。
     */
    public function test_user_relationship_is_belongs_to(): void
    {
        $readingPlan = new ReadingPlan;

        $this->assertInstanceOf(
            BelongsTo::class,
            $readingPlan->user(),
        );
    }

    /**
     * bookリレーションがBelongsToであることを確認する。
     */
    public function test_book_relationship_is_belongs_to(): void
    {
        $readingPlan = new ReadingPlan;

        $this->assertInstanceOf(
            BelongsTo::class,
            $readingPlan->book(),
        );
    }
}
