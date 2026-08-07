<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Profile;

use App\Actions\UpdateUserProfile;
use App\Http\Controllers\Controller;
use App\ViewModels\Settings\Account\Profile\ProfileViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.settings.account.profile.index', [
            'viewModel' => new ProfileViewModel(
                user: $request->user(),
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)],
        ]);

        new UpdateUserProfile(
            user: $request->user(),
            firstName: $validated['first_name'],
            lastName: $validated['last_name'],
            email: $validated['email'],
        )->execute();

        return redirect()->route('settings.profile.index')
            ->with('status', __('Your details are saved.'))
            ->with('status_description', __('They appear everywhere your name does.'));
    }
}
