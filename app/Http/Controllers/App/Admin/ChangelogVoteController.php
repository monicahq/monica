<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Http\Controllers\Controller;
use App\ViewModels\Admin\ChangelogVotesViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChangelogVoteController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.admin.changelog.votes', [
            'viewModel' => new ChangelogVotesViewModel(
                sort: ChangelogVotesViewModel::sortFrom($request->string('sort')->toString()),
            ),
        ]);
    }
}
