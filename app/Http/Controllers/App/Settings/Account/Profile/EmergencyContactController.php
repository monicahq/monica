<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Profile;

use App\Actions\UpdateEmergencyContact;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmergencyContactController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'relationship' => ['nullable', 'string', 'max:255'],
        ]);

        new UpdateEmergencyContact(
            user: $request->user(),
            name: $validated['name'] ?? null,
            phone: $validated['phone'] ?? null,
            relationship: $validated['relationship'] ?? null,
        )->execute();

        return redirect()->route('settings.profile.index')
            ->with('status', __('Your emergency contact is saved.'))
            ->with('status_description', __('Only you and your company administrators can see this.'));
    }
}
