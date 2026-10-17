<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\GrantAdministratorAccess;
use App\Actions\WithdrawAdministratorAccess;
use App\Exceptions\AdministratorCannotLockThemselvesOut;
use App\Exceptions\AdministratorCapacityIsAlreadyHeld;
use App\Exceptions\InstanceMustKeepAnAdministrator;
use App\Helpers\PageSize;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\ViewModels\Admin\AdministratorsViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdministratorController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.admin.administrators.index', [
            'viewModel' => new AdministratorsViewModel(
                size: PageSize::from($request, AdministratorsViewModel::PER_PAGE),
            ),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $user = User::query()
            ->whereRaw('lower(email) = ?', [mb_strtolower($validated['email'])])
            ->first();

        if ($user === null) {
            return back()->withErrors(['email' => __('Nobody on this instance uses that address.')]);
        }

        try {
            new GrantAdministratorAccess(
                user: $user,
                administrator: $request->user(),
            )->execute();
        } catch (AdministratorCapacityIsAlreadyHeld $exception) {
            return back()->withErrors(['email' => $exception->getMessage()]);
        }

        return redirect()->route('admin.administrators.index')
            ->with('status', __(':name administers this instance.', ['name' => $user->name]))
            ->with('status_description', __('They can reach the admin space from now on, and the grant is recorded against your name.'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        try {
            new WithdrawAdministratorAccess(
                user: $user,
                administrator: $request->user(),
            )->execute();
        } catch (AdministratorCannotLockThemselvesOut|InstanceMustKeepAnAdministrator $exception) {
            return back()->withErrors(['administrator' => $exception->getMessage()]);
        }

        return redirect()->route('admin.administrators.index')
            ->with('status', __(':name no longer administers this instance.', ['name' => $user->name]))
            ->with('status_description', __('The admin space is closed to them, including on a screen they already had open.'));
    }
}
