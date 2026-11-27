<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\DestroyAccountAsAdministrator;
use App\Exceptions\AdministratorCannotLockThemselvesOut;
use App\Exceptions\ProviderUnreachable;
use App\Helpers\PageSize;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\ViewModels\Admin\AccountsViewModel;
use App\ViewModels\Admin\AccountViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    public function destroy(Request $request, Account $account): RedirectResponse
    {
        $expected = mb_strtolower($account->userIncludingDeleted->email ?? (string) $account->id);

        $request->merge(['confirmation' => mb_strtolower($request->string('confirmation')->trim()->toString())]);

        $request->validate([
            'confirmation' => ['required', 'string', Rule::in([$expected])],
        ], [
            'confirmation.in' => __('Type :value exactly as it is shown.', ['value' => $expected]),
        ]);

        try {
            new DestroyAccountAsAdministrator(
                account: $account,
                administrator: $request->user(),
            )->execute();
        } catch (AdministratorCannotLockThemselvesOut $exception) {
            return back()->withErrors(['deletion' => $exception->getMessage()]);
        } catch (ProviderUnreachable $exception) {
            return back()->withErrors(['confirmation' => $exception->getMessage()]);
        }

        return redirect()->route('admin.accounts.index')
            ->with('status', __('The account is deleted.'))
            ->with('status_description', __('Everything in it is gone, the files it stored included. The administrator log keeps its number and nothing else.'));
    }
}
