<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CastChangelogVote;
use App\Actions\WithdrawChangelogVote;
use App\Enums\ChangelogVoteDirectionEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ChangelogVoteController extends Controller
{
    public function update(Request $request, string $entry): JsonResponse
    {
        $validated = $request->validate([
            'direction' => ['required', Rule::enum(ChangelogVoteDirectionEnum::class)],
        ]);

        $vote = new CastChangelogVote(
            user: $request->user(),
            entryId: $entry,
            direction: ChangelogVoteDirectionEnum::from($validated['direction']),
        )->execute();

        return response()->json([
            'data' => [
                'type' => 'changelog_vote',
                'id' => $entry,
                'attributes' => [
                    'direction' => $vote->direction->value,
                    'updated_at' => $vote->updated_at?->timestamp,
                ],
            ],
        ], 200);
    }

    public function destroy(Request $request, string $entry): Response
    {
        new WithdrawChangelogVote(
            user: $request->user(),
            entryId: $entry,
        )->execute();

        return response()->noContent(204);
    }
}
