<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Http\Controllers\Controller;
use App\ViewModels\Admin\ChangelogModerationViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChangelogCommentController extends Controller
{
    public function index(Request $request): View
    {
        $entry = $request->string('entry')->toString();

        return view('app.admin.changelog.index', [
            'viewModel' => new ChangelogModerationViewModel(
                viewer: $request->user(),
                tab: ChangelogModerationViewModel::tabFrom($request->string('tab')->toString()),
                entry: $entry === '' ? null : $entry,
                search: $request->string('search')->trim()->toString(),
                type: $request->string('type')->toString(),
                visibility: $request->string('visibility')->toString(),
                period: ChangelogModerationViewModel::periodFrom($request->string('period')->toString()),
            ),
        ]);
    }
}
