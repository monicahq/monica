<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Actions\PostChangelogComment;
use App\Helpers\ChangelogEntries;
use App\Helpers\ChangelogEntry;
use App\Http\Controllers\Controller;
use App\ViewModels\ChangelogCommentsViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use InvalidArgumentException;

class ChangelogCommentController extends Controller
{
    public function index(Request $request, string $entry): View
    {
        abort_unless(app(ChangelogEntries::class)->find($entry) instanceof ChangelogEntry, 404);

        return view('app.changelog._comments', [
            'viewModel' => new ChangelogCommentsViewModel(
                user: $request->user(),
                entryId: $entry,
            ),
        ]);
    }

    public function create(Request $request, string $entry): RedirectResponse
    {
        $thread = redirect()->route('changelog.comments.index', ['entry' => $entry]);

        $validator = Validator::make($request->all(), [
            'body' => ['required', 'string', 'max:'.PostChangelogComment::MAXIMUM_LENGTH],
            'visibility' => ['required', 'in:public,private'],
            'identity' => ['nullable', 'in:self,anonymous'],
        ]);

        if ($validator->fails()) {
            return $thread->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        try {
            new PostChangelogComment(
                user: $request->user(),
                entryId: $entry,
                body: $validated['body'],
                isPrivate: $validated['visibility'] === 'private',
                isAnonymous: ($validated['identity'] ?? 'self') === 'anonymous',
            )->execute();
        } catch (InvalidArgumentException $e) {
            return $thread->withErrors(['body' => $e->getMessage()])->withInput();
        }

        return $thread->with('status', __('Your comment is posted.'));
    }
}
