<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Profile;

use App\Actions\UpdateEmployeeInformation;
use App\Http\Controllers\Controller;
use App\ViewModels\Settings\Account\Profile\ProfileViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.settings.account.profile.index', [
            'viewModel' => new ProfileViewModel(
                user: $request->user(),
                employee: $request->user()->employee,
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'work_email' => ['nullable', 'string', 'email', 'max:255'],
        ]);

        new UpdateEmployeeInformation(
            user: $request->user(),
            firstName: $validated['first_name'],
            lastName: $validated['last_name'],
            displayName: $validated['display_name'] ?? null,
            workEmail: $validated['work_email'] ?? null,
        )->execute();

        return redirect()->route('settings.profile.index')
            ->with('status', __('Your details are saved.'))
            ->with('status_description', __('Your colleagues see them right away.'));
    }
}
