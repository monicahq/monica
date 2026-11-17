<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\PostChangelogComment;
use App\Helpers\ChangelogEntries;
use App\Helpers\ChangelogEntry;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChangelogCommentResource;
use App\Models\ChangelogComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ChangelogCommentController extends Controller
{
    public function index(Request $request, string $entry): AnonymousResourceCollection
    {
        abort_unless(app(ChangelogEntries::class)->find($entry) instanceof ChangelogEntry, 404);

        $comments = ChangelogComment::query()
            ->with(['user', 'replies'])
            ->where('entry_id', $entry)
            ->visibleTo($request->user())
            ->orderBy('id')
            ->get();

        return ChangelogCommentResource::collection($comments);
    }

    public function create(Request $request, string $entry): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:'.PostChangelogComment::MAXIMUM_LENGTH],
            'is_private' => ['sometimes', 'boolean'],
            'is_anonymous' => ['sometimes', 'boolean'],
        ]);

        try {
            $comment = new PostChangelogComment(
                user: $request->user(),
                entryId: $entry,
                body: $validated['body'],
                isPrivate: (bool) ($validated['is_private'] ?? false),
                isAnonymous: (bool) ($validated['is_anonymous'] ?? false),
            )->execute();
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['body' => $e->getMessage()]);
        }

        return new ChangelogCommentResource($comment->load(['user', 'replies']))
            ->response()
            ->setStatusCode(201);
    }
}
