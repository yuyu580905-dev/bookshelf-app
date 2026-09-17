<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\ReadingPlanStoreRequest;
use App\Http\Requests\ReadingPlanUpdateRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 読書計画に関する処理を管理するコントローラー。
 */
class ReadingPlanController extends Controller
{
    /**
     * ログインユーザーの読書計画一覧を表示する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @return View 読書計画一覧画面
     */
    public function index(Request $request): View
    {
        $currentStatus = $request->query('status');

        $readingPlans = $request->user()
            ->readingPlans()
            ->with('book')
            ->when(
                $currentStatus,
                fn ($query) => $query->where('status', $currentStatus)
            )
            ->get();

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    /**
     * 読書計画作成画面を表示する。
     *
     * @return View 読書計画作成画面
     */
    public function create(): View
    {
        $books = Book::query()
            ->orderBy('title')
            ->get();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 新しい読書計画を登録する。
     *
     * @param  ReadingPlanStoreRequest  $request  読書計画登録リクエスト
     * @return RedirectResponse 読書計画一覧画面へのリダイレクト
     */
    public function store(ReadingPlanStoreRequest $request): RedirectResponse
    {
        $request->user()->readingPlans()->create([
            'book_id' => $request->integer('book_id'),
            'target_date' => $request->date('target_date'),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を登録しました。');
    }

    /**
     * 読書計画を読了状態に変更する。
     *
     * @param  ReadingPlan  $readingPlan  読書計画
     * @return RedirectResponse 読書計画一覧画面へのリダイレクト
     */
    public function complete(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('complete', $readingPlan);

        $readingPlan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を読了にしました。');
    }

    /**
     * 読書計画編集画面を表示する。
     *
     * @param  ReadingPlan  $readingPlan  読書計画
     * @return View 読書計画編集画面
     */
    public function edit(ReadingPlan $readingPlan): View
    {
        $this->authorize('update', $readingPlan);

        $readingPlan->load('book');

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * 読書計画を更新する。
     *
     * @param  ReadingPlanUpdateRequest  $request  読書計画更新リクエスト
     * @param  ReadingPlan  $readingPlan  読書計画
     * @return RedirectResponse 読書計画一覧画面へのリダイレクト
     */
    public function update(
        ReadingPlanUpdateRequest $request,
        ReadingPlan $readingPlan
    ): RedirectResponse {
        $this->authorize('update', $readingPlan);

        $readingPlan->update([
            'target_date' => $request->date('target_date'),
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を更新しました。');
    }

    /**
     * 読書計画を削除する。
     *
     * @param  ReadingPlan  $readingPlan  読書計画
     * @return RedirectResponse 読書計画一覧画面へのリダイレクト
     */
    public function destroy(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('delete', $readingPlan);

        $readingPlan->delete();

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を削除しました。');
    }
}
