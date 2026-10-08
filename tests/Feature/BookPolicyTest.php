<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookPolicyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 書籍作成者本人は書籍の更新権限を持つ。
     */
    public function test_owner_can_update_book(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()
            ->for($user)
            ->create();

        $this->assertTrue($user->can('update', $book));
    }

    /**
     * 他ユーザーは書籍の更新権限を持たない。
     */
    public function test_other_user_cannot_update_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()
            ->for($owner)
            ->create();

        $this->assertFalse($otherUser->can('update', $book));
    }
}
