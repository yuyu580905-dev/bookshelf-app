<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all()->keyBy('email');
        $books = Book::pluck('id', 'isbn');

        $users['yamada@example.com']
            ->favoriteBooks()
            ->syncWithoutDetaching([
                $books['9784101010014'], // 吾輩は猫である
                $books['9784873115658'], // リーダブルコード
                $books['9784863940246'], // 7つの習慣
                $books['9784478025819'], // 嫌われる勇気
            ]);

        $users['suzuki@example.com']
            ->favoriteBooks()
            ->syncWithoutDetaching([
                $books['9784422100524'], // 人を動かす
                $books['9784101010021'], // 坊っちゃん
                $books['9784309226712'], // サピエンス全史
                $books['9784163902302'], // 火花
                $books['9784822289607'], // FACTFULNESS
            ]);

        $users['tanaka@example.com']
            ->favoriteBooks()
            ->syncWithoutDetaching([
                $books['9784101010014'], // 吾輩は猫である
                $books['9784048930598'], // Clean Code
                $books['9784478025819'], // 嫌われる勇気
            ]);

        $users['sato@example.com']
            ->favoriteBooks()
            ->syncWithoutDetaching([
                $books['9784422100524'], // 人を動かす
                $books['9784863940246'], // 7つの習慣
                $books['9784822289607'], // FACTFULNESS
                $books['9784822251468'], // コンテナ物語
            ]);

        $users['takahashi@example.com']
            ->favoriteBooks()
            ->syncWithoutDetaching([
                $books['9784101010021'], // 坊っちゃん
                $books['9784309226712'], // サピエンス全史
                $books['9784048930598'], // Clean Code
                $books['9784163902302'], // 火花
            ]);
    }
}
