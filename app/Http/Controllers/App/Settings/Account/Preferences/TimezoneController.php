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
    /**
     * Set the time zone, named or pointed at.
     *
     * Two shapes reach here and they mean different things. A name is what
     * somebody chose, from the search field or from their own browser. A
     * position is where they clicked on the map, and the nearest zone to it is
     * a guess: the screen has to say so, and which shape arrived is what tells
     * it. Nothing the browser claims decides that.
     *
     * A position is resolved here rather than in the browser because working
     * out the nearest of four hundred zones, across the antimeridian, is worth
     * a test, and the tests in this project are written in PHP.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'timezone' => ['required_without:latitude', 'string', 'timezone:all'],
            // Nullable because the map's two fields are in the form whichever way
            // the zone is chosen, and they ride along empty when it was named.
            // Empty is not a number, so without this every pick from the list
            // fails on a field the reader never touched.
            'latitude' => ['required_without:timezone', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['required_with:latitude', 'nullable', 'numeric', 'between:-180,180'],
        ], [
            // The default message says the value is not a valid time zone, which
            // is not the reader's problem: theirs is that this one is not offered.
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
