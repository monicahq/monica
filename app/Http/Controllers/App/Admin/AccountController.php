<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Helpers\PageSize;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\ViewModels\Admin\AccountsViewModel;
use App\ViewModels\Admin\AccountViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.admin.accounts.index', [
            'viewModel' => new AccountsViewModel(
                query: (string) $request->string('query'),
                size: PageSize::from($request, AccountsViewModel::PER_PAGE),
                state: (string) $request->string('state'),
                access: (string) $request->string('access'),
                joined: (string) $request->string('joined'),
                flag: (string) $request->string('flag'),
            ),
        ]);
    }

    public function show(Request $request, Account $account): View
    {
        return view('app.admin.accounts.show', [
            'viewModel' => new AccountViewModel(
                account: $account,
                viewer: $request->user(),
            ),
        ]);
    }
}
