<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::pluck('id', 'email');
        $books = Book::pluck('id', 'isbn');

        $reviews = [
            // 1. 吾輩は猫である
            [
                'user_id' => $users['yamada@example.com'],
                'book_id' => $books['9784101010014'],
                'rating' => 5,
                'comment' => '猫の視点から人間社会を描いているところが面白く、独特のユーモアを楽しめました。',
            ],
            [
                'user_id' => $users['suzuki@example.com'],
                'book_id' => $books['9784101010014'],
                'rating' => 4,
                'comment' => '登場人物の描写が個性的で、明治時代の雰囲気を感じながら読むことができました。',
            ],
            [
                'user_id' => $users['tanaka@example.com'],
                'book_id' => $books['9784101010014'],
                'rating' => 5,
                'comment' => '文章表現がとても印象的でした。猫から見た人間の姿が新鮮で面白かったです。',
            ],

            // 2. 人を動かす
            [
                'user_id' => $users['sato@example.com'],
                'book_id' => $books['9784422100524'],
                'rating' => 3,
                'comment' => '人との接し方について具体的な考え方が紹介されていて、仕事でも実践してみたいと思いました。',
            ],
            [
                'user_id' => $users['takahashi@example.com'],
                'book_id' => $books['9784422100524'],
                'rating' => 4,
                'comment' => '相手の立場を考えることの大切さがよく分かりました。何度も読み返したい内容です。',
            ],
            [
                'user_id' => $users['yamada@example.com'],
                'book_id' => $books['9784422100524'],
                'rating' => 5,
                'comment' => '人間関係について考え直すきっかけになりました。仕事だけでなく日常生活にも役立つ内容です。',
            ],

            // 3. リーダブルコード
            [
                'user_id' => $users['suzuki@example.com'],
                'book_id' => $books['9784873115658'],
                'rating' => 5,
                'comment' => '読みやすいコードを書くための考え方が具体的に説明されていて、とても参考になりました。',
            ],
            [
                'user_id' => $users['tanaka@example.com'],
                'book_id' => $books['9784873115658'],
                'rating' => 4,
                'comment' => '変数名や関数の付け方など、普段のコーディングを見直すきっかけになりました。',
            ],
            [
                'user_id' => $users['sato@example.com'],
                'book_id' => $books['9784873115658'],
                'rating' => 5,
                'comment' => 'コードを書く際に何を意識すればよいのかが整理されていて、実践に活かしやすい本でした。',
            ],

            // 4. 7つの習慣
            [
                'user_id' => $users['takahashi@example.com'],
                'book_id' => $books['9784863940246'],
                'rating' => 5,
                'comment' => '仕事や生活に対する考え方を見直すことができました。特に優先順位についての内容が印象的でした。',
            ],
            [
                'user_id' => $users['yamada@example.com'],
                'book_id' => $books['9784863940246'],
                'rating' => 4,
                'comment' => '自分から行動することの重要性を改めて感じました。長期的に実践していきたい内容です。',
            ],
            [
                'user_id' => $users['suzuki@example.com'],
                'book_id' => $books['9784863940246'],
                'rating' => 3,
                'comment' => '考え方を変えることで日々の行動も変えられると感じました。自己成長を考える人におすすめです。',
            ],

            // 5. 坊っちゃん
            [
                'user_id' => $users['tanaka@example.com'],
                'book_id' => $books['9784101010021'],
                'rating' => 4,
                'comment' => '主人公の真っ直ぐな性格が印象的で、テンポよく物語を楽しむことができました。',
            ],
            [
                'user_id' => $users['sato@example.com'],
                'book_id' => $books['9784101010021'],
                'rating' => 5,
                'comment' => '登場人物同士のやり取りが面白く、最後まで飽きずに読むことができました。',
            ],
            [
                'user_id' => $users['takahashi@example.com'],
                'book_id' => $books['9784101010021'],
                'rating' => 4,
                'comment' => '昔の作品ですが、登場人物の人間らしさが伝わってきて楽しく読めました。',
            ],

            // 6. サピエンス全史
            [
                'user_id' => $users['yamada@example.com'],
                'book_id' => $books['9784309226712'],
                'rating' => 5,
                'comment' => '人類の歴史を大きな視点から捉えることができ、とても興味深い内容でした。',
            ],
            [
                'user_id' => $users['suzuki@example.com'],
                'book_id' => $books['9784309226712'],
                'rating' => 3,
                'comment' => '歴史を単なる出来事ではなく、人類全体の変化として考えられる点が面白かったです。',
            ],
            [
                'user_id' => $users['tanaka@example.com'],
                'book_id' => $books['9784309226712'],
                'rating' => 5,
                'comment' => '普段あまり考えない人類の歴史について、幅広い視点を持つことができました。',
            ],

            // 7. Clean Code
            [
                'user_id' => $users['sato@example.com'],
                'book_id' => $books['9784048930598'],
                'rating' => 5,
                'comment' => '保守しやすいコードを書くための考え方が体系的にまとめられていて勉強になりました。',
            ],
            [
                'user_id' => $users['takahashi@example.com'],
                'book_id' => $books['9784048930598'],
                'rating' => 4,
                'comment' => '普段書いているコードを見直すきっかけになりました。チーム開発でも役立ちそうです。',
            ],
            [
                'user_id' => $users['yamada@example.com'],
                'book_id' => $books['9784048930598'],
                'rating' => 4,
                'comment' => '読みやすさだけでなく、変更しやすいコードを書くことの重要性を理解できました。',
            ],

            // 8. 嫌われる勇気
            [
                'user_id' => $users['suzuki@example.com'],
                'book_id' => $books['9784478025819'],
                'rating' => 3,
                'comment' => '自分の考え方を見直すきっかけになりました。対話形式なので読みやすかったです。',
            ],
            [
                'user_id' => $users['tanaka@example.com'],
                'book_id' => $books['9784478025819'],
                'rating' => 4,
                'comment' => '他人からどう見られるかを気にしすぎていた自分に気付くことができました。',
            ],
            [
                'user_id' => $users['sato@example.com'],
                'book_id' => $books['9784478025819'],
                'rating' => 5,
                'comment' => '自分の人生を自分で選択するという考え方が印象に残りました。読みやすい一冊です。',
            ],

            // 9. 火花
            [
                'user_id' => $users['takahashi@example.com'],
                'book_id' => $books['9784163902302'],
                'rating' => 4,
                'comment' => '芸人を目指す二人の関係性が丁寧に描かれていて、物語に引き込まれました。',
            ],
            [
                'user_id' => $users['yamada@example.com'],
                'book_id' => $books['9784163902302'],
                'rating' => 4,
                'comment' => '夢を追うことの難しさや人との出会いについて考えさせられる作品でした。',
            ],
            [
                'user_id' => $users['suzuki@example.com'],
                'book_id' => $books['9784163902302'],
                'rating' => 5,
                'comment' => '登場人物の感情が伝わってきて、最後まで興味深く読むことができました。',
            ],

            // 10. FACTFULNESS
            [
                'user_id' => $users['tanaka@example.com'],
                'book_id' => $books['9784822289607'],
                'rating' => 4,
                'comment' => '思い込みではなくデータをもとに世界を見ることの重要性がよく分かりました。',
            ],
            [
                'user_id' => $users['sato@example.com'],
                'book_id' => $books['9784822289607'],
                'rating' => 3,
                'comment' => 'ニュースや統計を見るときにも役立つ考え方が多く、勉強になりました。',
            ],
            [
                'user_id' => $users['takahashi@example.com'],
                'book_id' => $books['9784822289607'],
                'rating' => 3,
                'comment' => '世界に対する先入観に気付かされました。数字を正しく見ることを意識したいと思います。',
            ],

            // 11. コンテナ物語
            [
                'user_id' => $users['yamada@example.com'],
                'book_id' => $books['9784822251468'],
                'rating' => 5,
                'comment' => 'コンテナが世界の物流を大きく変えたことを知り、普段の貿易についても興味が湧きました。',
            ],
            [
                'user_id' => $users['takahashi@example.com'],
                'book_id' => $books['9784822251468'],
                'rating' => 5,
                'comment' => '物流の歴史を知らなくても読みやすく、社会の仕組みについて考えるきっかけになりました。',
            ],
        ];

        foreach ($reviews as $review) {
            Review::create($review);
        }
    }
}
