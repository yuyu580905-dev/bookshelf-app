<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 正常な入力で書籍が登録され、ジャンルが紐付けられる
     */
    public function test_book_can_be_created(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
            'user_id' => $user->id,
        ];

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertCreated();

        $this->assertDatabaseHas('books', [
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
            'user_id' => $user->id,
        ]);

        $book = Book::where('isbn', '1234567890123')->first();

        $this->assertNotNull($book);
        $this->assertTrue($book->genres->contains($genre));
    }

    /**
     * 必須項目が未入力の場合はバリデーションエラーになる
     */
    public function test_required_fields_are_validated(): void
    {
        $data = $this->validBookData();

        unset(
            $data['title'],
            $data['author'],
            $data['isbn'],
            $data['published_date'],
            $data['genres'],
            $data['user_id']
        );

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title' => 'タイトルは必須です。',
                'author' => '著者は必須です。',
                'isbn' => 'ISBNは必須です。',
                'published_date' => '出版日は必須です。',
                'genres' => 'ジャンルは1つ以上選択してください。',
                'user_id' => '登録者IDは必須です。',
            ]);
    }

    /**
     * ISBNは13桁で一意でなければならない
     */
    public function test_isbn_must_be_valid(): void
    {
        $data = $this->validBookData();
        $data['isbn'] = '123456789012';

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'isbn' => 'ISBNは13桁で入力してください。',
            ]);

        $data = $this->validBookData();
        $existingBook = Book::factory()->create();

        $data['isbn'] = $existingBook->isbn;

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'isbn' => 'そのISBNは既に使用されています。',
            ]);
    }

    /**
     * 出版日が有効な日付でない場合はバリデーションエラーになる
     */
    public function test_published_date_must_be_valid(): void
    {
        $data = $this->validBookData();
        $data['published_date'] = 'invalid-date';

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'published_date' => '出版日は有効な日付形式で入力してください。',
            ]);
    }

    /**
     * 画像URLが有効なURLでない場合はバリデーションエラーになる
     */
    public function test_image_url_must_be_valid(): void
    {
        $data = $this->validBookData();
        $data['image_url'] = 'invalid-url';

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'image_url' => '画像URLは有効なURL形式で入力してください。',
            ]);
    }

    /**
     * ジャンルは配列で1つ以上指定し、存在するジャンルでなければならない
     */
    public function test_genres_must_be_valid(): void
    {
        $data = $this->validBookData();

        $data['genres'] = 'invalid';

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'genres' => 'ジャンルは配列で入力してください。',
            ]);

        $data = $this->validBookData();

        $data['genres'] = [];

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'genres' => 'ジャンルは1つ以上選択してください。',
            ]);

        $data = $this->validBookData();

        $data['genres'] = [99999];

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'genres.0' => '選択されたジャンルは存在しません。',
            ]);
    }

    /**
     * 登録者IDは整数で、存在するユーザーでなければならない
     */
    public function test_user_id_must_be_valid(): void
    {
        $data = $this->validBookData();
        $data['user_id'] = 'invalid';

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'user_id' => '登録者IDは整数で入力してください。',
            ]);

        $data = $this->validBookData();
        $data['user_id'] = 99999;

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'user_id' => '指定された登録者は存在しません。',
            ]);
    }

    /**
     * 正常な書籍登録データを作成する
     */
    private function validBookData(): array
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        return [
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
            'user_id' => $user->id,
        ];
    }
}
