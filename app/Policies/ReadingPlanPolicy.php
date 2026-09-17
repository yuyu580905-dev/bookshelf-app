<?php

namespace App\Policies;

use App\Models\ReadingPlan;
use App\Models\User;

class ReadingPlanPolicy
{
    /**
     * 読書計画の更新を許可する。
     *
     * @param  User  $user  ログインユーザー
     * @param  ReadingPlan  $readingPlan  読書計画
     * @return bool 所有者の場合はtrue
     */
    public function update(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id;
    }

    /**
     * 読書計画の削除を許可する。
     *
     * @param  User  $user  ログインユーザー
     * @param  ReadingPlan  $readingPlan  読書計画
     * @return bool 所有者の場合はtrue
     */
    public function delete(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id;
    }

    /**
     * 読書計画の読了処理を許可する。
     *
     * @param  User  $user  ログインユーザー
     * @param  ReadingPlan  $readingPlan  読書計画
     * @return bool 所有者の場合はtrue
     */
    public function complete(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id;
    }
}
