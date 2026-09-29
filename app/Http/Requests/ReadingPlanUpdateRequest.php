<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 読書計画更新時のバリデーションを定義するFormRequest。
 */
class ReadingPlanUpdateRequest extends FormRequest
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
            'target_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'target_date.required' => '期日を入力してください。',
            'target_date.date' => '期日は有効な日付形式で入力してください。',
            'target_date.after_or_equal' => '期日は未来の日付を入力してください。',
        ];
    }
}
