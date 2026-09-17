<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 読書計画登録時のバリデーションを定義するFormRequest。
 */
class ReadingPlanStoreRequest extends FormRequest
{
    /**
     * リクエストの認可を判定する。
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * バリデーションルールを返す。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'target_date' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'book_id.required' => '書籍は必須です。',
            'book_id.integer' => '書籍IDは整数で入力してください。',
            'book_id.exists' => '選択された書籍は存在しません。',
            'target_date.required' => '期日を入力してください。',
            'target_date.date' => '期日は有効な日付形式で入力してください。',
        ];
    }
}
