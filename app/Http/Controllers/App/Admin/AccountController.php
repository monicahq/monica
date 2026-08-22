<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

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
            ),
        ]);
    }

    public function show(Account $account): View
    {
        return view('app.admin.accounts.show', [
            'viewModel' => new AccountViewModel(
                account: $account,
            ),
        ]);
    }
}
