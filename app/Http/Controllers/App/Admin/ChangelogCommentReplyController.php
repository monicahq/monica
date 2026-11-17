<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\ReplyToChangelogComment;
use App\Http\Controllers\Controller;
use App\Models\ChangelogComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ChangelogCommentReplyController extends Controller
{
    public function create(Request $request, string $comment): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:20000'],
        ]);

        try {
            new ReplyToChangelogComment(
                administrator: $request->user(),
                comment: ChangelogComment::query()->where('uuid', $comment)->firstOrFail(),
                body: $validated['body'],
            )->execute();
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['body' => $e->getMessage()]);
        }

        return back()->with('status', __('Your reply is sent.'));
    }
}
