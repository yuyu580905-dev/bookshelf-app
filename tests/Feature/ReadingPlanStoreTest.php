<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストは読書計画を登録できず、ログイン画面へリダイレクトされる。
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $book = Book::factory()->create();

        $response = $this->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => '2026-10-01',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseCount('reading_plans', 0);
    }

    /**
     * 認証ユーザーは読書計画を正常に登録できる。
     */
    public function test_authenticated_user_can_store_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => '2026-11-01',
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => '2026-11-01',
            'status' => ReadingPlanStatus::InProgress->value,
            'completed_at' => null,
        ]);
    }

    /**
     * 同一ユーザーが同一書籍のin_progress読書計画を重複して登録できない。
     */
    public function test_same_user_cannot_store_duplicate_in_progress_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => '2026-11-01',
            ]);

        $response->assertSessionHasErrors('book_id');

        $this->assertDatabaseCount('reading_plans', 1);
    }

    /**
     * 同一ユーザーでも同一書籍のcompleted読書計画があれば新しい計画を登録できる。
     */
    public function test_same_user_can_store_reading_plan_for_completed_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => '2026-11-01',
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseCount('reading_plans', 2);

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => '2026-11-01',
            'status' => ReadingPlanStatus::InProgress->value,
            'completed_at' => null,
        ]);
    }

    /**
     * 別ユーザーが同一書籍のin_progress読書計画を登録できる。
     */
    public function test_different_user_can_store_same_book_in_progress(): void
    {
        $existingUser = User::factory()->create();
        $newUser = User::factory()->create();
        $book = Book::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $existingUser->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($newUser)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => '2026-11-01',
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $newUser->id,
            'book_id' => $book->id,
            'target_date' => '2026-11-01',
            'status' => ReadingPlanStatus::InProgress->value,
            'completed_at' => null,
        ]);
    }

    /**
     * book_idが未入力の場合はバリデーションエラーになる。
     */
    public function test_book_id_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'target_date' => '2026-10-01',
            ]);

        $response->assertSessionHasErrors('book_id');

        $this->assertDatabaseCount('reading_plans', 0);
    }

    /**
     * 存在しないbook_idの場合はバリデーションエラーになる。
     */
    public function test_book_id_must_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => 999999,
                'target_date' => '2026-10-01',
            ]);

        $response->assertSessionHasErrors('book_id');

        $this->assertDatabaseCount('reading_plans', 0);
    }

    /**
     * target_dateが未入力の場合はバリデーションエラーになる。
     */
    public function test_target_date_is_required(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
            ]);

        $response->assertSessionHasErrors('target_date');

        $this->assertDatabaseCount('reading_plans', 0);
    }

    /**
     * target_dateが日付形式でない場合はバリデーションエラーになる。
     */
    public function test_target_date_must_be_a_valid_date(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => 'not-a-date',
            ]);

        $response->assertSessionHasErrors('target_date');

        $this->assertDatabaseCount('reading_plans', 0);
    }
}
