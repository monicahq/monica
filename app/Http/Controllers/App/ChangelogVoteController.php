<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Actions\CastChangelogVote;
use App\Actions\WithdrawChangelogVote;
use App\Enums\ChangelogVoteDirectionEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChangelogVoteController extends Controller
{
    public function update(Request $request, string $entry): RedirectResponse
    {
        $validated = $request->validate([
            'direction' => ['required', Rule::enum(ChangelogVoteDirectionEnum::class)],
        ]);

        new CastChangelogVote(
            user: $request->user(),
            entryId: $entry,
            direction: ChangelogVoteDirectionEnum::from($validated['direction']),
        )->execute();

        return back();
    }

    public function destroy(Request $request, string $entry): RedirectResponse
    {
        new WithdrawChangelogVote(
            user: $request->user(),
            entryId: $entry,
        )->execute();

        return back();
    }
}
