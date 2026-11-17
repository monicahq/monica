<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\HideChangelogComment;
use App\Actions\ShowChangelogComment;
use App\Http\Controllers\Controller;
use App\Models\ChangelogComment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChangelogCommentHidingController extends Controller
{
    public function create(Request $request, string $comment): RedirectResponse
    {
        try {
            new HideChangelogComment(
                administrator: $request->user(),
                comment: ChangelogComment::query()->where('uuid', $comment)->firstOrFail(),
            )->execute();
        } catch (AuthorizationException $e) {
            return back()->withErrors(['comment' => $e->getMessage()]);
        }

        return back()->with('status', __('The comment is hidden from What’s new.'));
    }

    public function destroy(Request $request, string $comment): RedirectResponse
    {
        new ShowChangelogComment(
            administrator: $request->user(),
            comment: ChangelogComment::query()->where('uuid', $comment)->firstOrFail(),
        )->execute();

        return back()->with('status', __('The comment is shown on What’s new again.'));
    }
}
