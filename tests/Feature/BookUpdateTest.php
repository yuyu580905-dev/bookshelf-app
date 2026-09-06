<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 書籍作成者本人が書籍情報を更新できる（ジャンルの更新も含む）
     */
    public function test_owner_can_update_book(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()
            ->for($user)
            ->create();

        $oldGenres = Genre::factory()->count(2)->create();
        $newGenres = Genre::factory()->count(2)->create();

        $book->genres()->attach($oldGenres);

        $data = [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-09-05',
            'description' => '更新後の説明です。',
            'image_url' => 'https://example.com/updated.jpg',
            'genres' => $newGenres->pluck('id')->toArray(),
        ];

        $response = $this->actingAs($user)
            ->put(route('books.update', $book), $data);

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-09-05',
            'description' => '更新後の説明です。',
            'image_url' => 'https://example.com/updated.jpg',
        ]);

        $this->assertEquals(
            $newGenres->pluck('id')->sort()->values()->toArray(),
            $book->fresh()->genres->pluck('id')->sort()->values()->toArray()
        );
    }

    /**
     * 書籍作成者本人がISBNを変更せずに更新できる
     */
    public function test_owner_can_keep_the_same_isbn(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()
            ->for($user)
            ->create([
                'isbn' => '9781234567890',
            ]);

        $genre = Genre::factory()->create();

        $data = [
            'title' => '更新後のタイトル',
            'author' => $book->author,
            'isbn' => $book->isbn,
            'published_date' => $book->published_date,
            'description' => $book->description,
            'image_url' => $book->image_url,
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->put(route('books.update', $book), $data);

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => '9781234567890',
            'title' => '更新後のタイトル',
        ]);
    }

    /**
     * 書籍作成者本人がISBNを他の書籍と重複させて更新しようとするとエラーになる
     */
    public function test_update_is_rejected_when_isbn_is_already_used_by_another_book(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()
            ->for($user)
            ->create([
                'isbn' => '9781234567890',
            ]);

        $otherBook = Book::factory()->create([
            'isbn' => '9780987654321',
        ]);

        $genre = Genre::factory()->create();

        $data = [
            'title' => '更新後のタイトル',
            'author' => $book->author,
            'isbn' => $otherBook->isbn,
            'published_date' => $book->published_date,
            'description' => $book->description,
            'image_url' => $book->image_url,
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->put(route('books.update', $book), $data);

        $response->assertSessionHasErrors('isbn');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => '9781234567890',
        ]);
    }

    /**
     * 他ユーザーが他人の書籍情報を更新しようとすると403になる
     */
    public function test_other_user_cannot_update_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()
            ->for($owner)
            ->create();

        $genre = Genre::factory()->create();

        $data = [
            'title' => '不正な更新',
            'author' => '不正な著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-09-05',
            'description' => '不正な説明',
            'image_url' => 'https://example.com/invalid.jpg',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($otherUser)
            ->put(route('books.update', $book), $data);

        $response->assertForbidden();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '不正な更新',
        ]);
    }

    /**
     * ゲストユーザーが書籍情報を更新しようとするとログインページにリダイレクトされる
     */
    public function test_guest_is_redirected_to_login_when_updating_book(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()
            ->for($user)
            ->create();

        $genre = Genre::factory()->create();

        $data = [
            'title' => '更新後のタイトル',
            'author' => $book->author,
            'isbn' => $book->isbn,
            'published_date' => $book->published_date,
            'description' => $book->description,
            'image_url' => $book->image_url,
            'genres' => [$genre->id],
        ];

        $response = $this->put(route('books.update', $book), $data);

        $response->assertRedirect(route('login'));
    }

    /**
     * 書籍作成者本人が必須項目を欠いた状態で更新しようとするとエラーになる
     */
    public function test_update_is_rejected_when_required_fields_are_missing(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()
            ->for($user)
            ->create();

        $response = $this->actingAs($user)
            ->put(route('books.update', $book), []);

        $response->assertSessionHasErrors([
            'title',
            'author',
            'isbn',
            'published_date',
            'genres',
        ]);
    }
}
