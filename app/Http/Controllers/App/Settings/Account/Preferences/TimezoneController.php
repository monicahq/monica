<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Preferences;

use App\Actions\UpdateTimezone;
use App\Helpers\TimezoneCatalogue;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TimezoneController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'timezone' => ['required_without:latitude', 'string', 'timezone:all'],
            'latitude' => ['required_without:timezone', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['required_with:latitude', 'nullable', 'numeric', 'between:-180,180'],
        ], [
            'timezone.timezone' => __('That is not a time zone the product knows. Search for the nearest city instead.'),
        ]);

        $pointedAt = ! array_key_exists('timezone', $validated);

        $timezone = $pointedAt
            ? TimezoneCatalogue::nearest((float) $validated['latitude'], (float) $validated['longitude'])['timezone']
            : $validated['timezone'];

        new UpdateTimezone(
            user: $request->user(),
            timezone: $timezone,
        )->execute();

        return redirect()->route('settings.preferences.index')
            ->with('status', __('Your time zone is saved.'))
            ->with('status_description', __('Dates and times now read the way they do where you are.'))
            ->with('timezone_guess', $pointedAt);
    }
}
