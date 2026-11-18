<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Actions\MarkChangelogSeen;
use App\Helpers\ChangelogEntries;
use App\Helpers\ChangelogEntry;
use App\Http\Controllers\Controller;
use App\ViewModels\ChangelogViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChangelogController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $unseen = [];

        if ($user !== null) {
            $unseen = app(ChangelogEntries::class)->unseenBy($user);

            new MarkChangelogSeen(user: $user)->execute();
        }

        return view('app.changelog.index', [
            'viewModel' => new ChangelogViewModel(
                user: $user,
                unseenIds: array_map(fn (ChangelogEntry $entry): string => $entry->id, $unseen),
                period: ChangelogEntries::period($request->string('period')->toString()),
            ),
        ]);
    }
}
