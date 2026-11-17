<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\ReviewChangelogComment;
use App\Http\Controllers\Controller;
use App\Models\ChangelogComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChangelogCommentReviewController extends Controller
{
    public function create(Request $request, string $comment): RedirectResponse
    {
        new ReviewChangelogComment(
            administrator: $request->user(),
            comment: ChangelogComment::query()->where('uuid', $comment)->firstOrFail(),
        )->execute();

        return back()->with('status', __('The comment is reviewed.'));
    }
}
