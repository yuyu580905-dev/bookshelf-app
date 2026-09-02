<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookStoreTest extends TestCase
{
    use RefreshDatabase;

    // 正常な入力で書籍が登録され、ジャンルが紐付けられる
    public function test_book_can_be_created_with_genres(): void
    {
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        $data = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000001',
            'published_date' => '2026-09-01',
            'description' => 'テスト用の説明です。',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => $genres->pluck('id')->toArray(),
        ];

        $response = $this->actingAs($user)
            ->post('/books', $data);

        $book = Book::where('isbn', $data['isbn'])->first();

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => $data['title'],
            'author' => $data['author'],
            'isbn' => $data['isbn'],
            'published_date' => $data['published_date'],
            'description' => $data['description'],
            'image_url' => $data['image_url'],
        ]);

        foreach ($genres as $genre) {
            $this->assertDatabaseHas('book_genre', [
                'book_id' => $book->id,
                'genre_id' => $genre->id,
            ]);
        }
    }

    // 必須項目が未入力の場合はバリデーションエラーになる
    public function test_required_fields_are_validated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/books', [
                'isbn' => '9784000000002',
                'published_date' => '2026-09-01',
                'genres' => [Genre::factory()->create()->id],
            ]);

        $response->assertSessionHasErrors([
            'title',
            'author',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    // ISBNが13桁でない場合はバリデーションエラーになる
    public function test_isbn_must_be_13_digits(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)
            ->post('/books', [
                'title' => 'テスト書籍',
                'author' => 'テスト著者',
                'isbn' => '123456789012',
                'published_date' => '2026-09-01',
                'genres' => [$genre->id],
            ]);

        $response->assertSessionHasErrors('isbn');

        $this->assertDatabaseCount('books', 0);
    }

    // ISBNが重複している場合はバリデーションエラーになる
    public function test_isbn_must_be_unique(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        Book::factory()->create([
            'isbn' => '9784000000003',
        ]);

        $response = $this->actingAs($user)
            ->post('/books', [
                'title' => 'テスト書籍',
                'author' => 'テスト著者',
                'isbn' => '9784000000003',
                'published_date' => '2026-09-01',
                'genres' => [$genre->id],
            ]);

        $response->assertSessionHasErrors('isbn');

        $this->assertDatabaseCount('books', 1);
    }

    // 出版日が有効な日付でない場合はバリデーションエラーになる
    public function test_published_date_must_be_valid_date(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)
            ->post('/books', [
                'title' => 'テスト書籍',
                'author' => 'テスト著者',
                'isbn' => '9784000000004',
                'published_date' => 'invalid-date',
                'genres' => [$genre->id],
            ]);

        $response->assertSessionHasErrors('published_date');

        $this->assertDatabaseCount('books', 0);
    }

    // ジャンルを1つ以上選択する必要がある
    public function test_at_least_one_genre_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/books', [
                'title' => 'テスト書籍',
                'author' => 'テスト著者',
                'isbn' => '9784000000005',
                'published_date' => '2026-09-01',
                'genres' => [],
            ]);

        $response->assertSessionHasErrors('genres');

        $this->assertDatabaseCount('books', 0);
    }

    // 存在しないジャンルIDは指定できない
    public function test_genres_must_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/books', [
                'title' => 'テスト書籍',
                'author' => 'テスト著者',
                'isbn' => '9784000000006',
                'published_date' => '2026-09-01',
                'genres' => [99999],
            ]);

        $response->assertSessionHasErrors('genres.0');

        $this->assertDatabaseCount('books', 0);
    }

    // 画像URLがURL形式でない場合はバリデーションエラーになる
    public function test_image_url_must_be_valid_url(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)
            ->post('/books', [
                'title' => 'テスト書籍',
                'author' => 'テスト著者',
                'isbn' => '9784000000007',
                'published_date' => '2026-09-01',
                'image_url' => 'not-a-url',
                'genres' => [$genre->id],
            ]);

        $response->assertSessionHasErrors('image_url');

        $this->assertDatabaseCount('books', 0);
    }

    // 未認証ユーザーはログイン画面へリダイレクトされる
    public function test_guest_cannot_create_book(): void
    {
        $genre = Genre::factory()->create();

        $response = $this->post('/books', [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000008',
            'published_date' => '2026-09-01',
            'genres' => [$genre->id],
        ]);

        $response->assertRedirect('/login');

        $this->assertDatabaseCount('books', 0);
    }
}
