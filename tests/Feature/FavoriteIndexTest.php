<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証済みユーザーのお気に入り書籍が一覧に表示される
     */
    public function test_authenticated_user_can_view_favorite_books(): void
    {
        $user = User::factory()->create();

        $favoriteBooks = Book::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $user->favoriteBooks()->attach($favoriteBooks->pluck('id'));

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertStatus(200);

        foreach ($favoriteBooks as $book) {
            $response->assertSee($book->title);
        }
    }

    /**
     * 他ユーザーのお気に入り書籍は一覧に表示されない
     */
    public function test_other_users_favorite_books_are_not_displayed(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $userFavoriteBook = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $otherUserFavoriteBook = Book::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $user->favoriteBooks()->attach($userFavoriteBook->id);
        $otherUser->favoriteBooks()->attach($otherUserFavoriteBook->id);

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertStatus(200);
        $response->assertSee($userFavoriteBook->title);
        $response->assertDontSee($otherUserFavoriteBook->title);
    }

    /**
     * お気に入り書籍が10件/ページでページネーションされる
     */
    public function test_favorite_books_are_paginated_by_10(): void
    {
        $user = User::factory()->create();

        $favoriteBooks = Book::factory()->count(11)->create([
            'user_id' => $user->id,
        ]);

        $user->favoriteBooks()->attach($favoriteBooks->pluck('id'));

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertStatus(200);

        $firstPageBooks = $favoriteBooks->take(10);
        $secondPageBook = $favoriteBooks->last();

        foreach ($firstPageBooks as $book) {
            $response->assertSee($book->title);
        }

        $response->assertDontSee($secondPageBook->title);

        $response = $this->actingAs($user)->get(
            route('favorites.index', ['page' => 2])
        );

        $response->assertStatus(200);
        $response->assertSee($secondPageBook->title);
    }

    /**
     * 未認証ユーザーがアクセスするとログイン画面へリダイレクトされる
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('favorites.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * お気に入り書籍のタイトルから書籍詳細画面へ遷移できる
     */
    public function test_favorite_book_title_links_to_book_detail(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $user->favoriteBooks()->attach($book->id);

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertStatus(200);
        $response->assertSee(
            route('books.show', $book),
            false
        );
    }
}
