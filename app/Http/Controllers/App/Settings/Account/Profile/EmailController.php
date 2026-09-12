<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Profile;

use App\Actions\UpdateUserProfile;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmailController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)],
        ]);

        new UpdateUserProfile(
            user: $request->user(),
            firstName: $request->user()->first_name,
            lastName: $request->user()->last_name,
            email: $validated['email'],
        )->execute();

        return redirect()->route('settings.profile.index')
            ->with('status', __('Your email address is saved.'))
            ->with('status_description', __('It is the address you sign in with, and the one we write to.'));
    }
}
