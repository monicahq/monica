<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Preferences;

use App\Actions\UpdatePreferences;
use App\Enums\TimeFormatEnum;
use App\Http\Controllers\Controller;
use App\ViewModels\Settings\Account\Preferences\PreferencesViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PreferenceController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.settings.account.preferences.index', [
            'viewModel' => new PreferencesViewModel(
                user: $request->user(),
                employee: $request->user()->employee,
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(array_keys(config('officelife.locales')))],
            'time_format' => ['required', 'string', Rule::enum(TimeFormatEnum::class)],
        ]);

        new UpdatePreferences(
            user: $request->user(),
            locale: $validated['locale'],
            timeFormat: TimeFormatEnum::from($validated['time_format']),
        )->execute();

        // The session holds whatever language was last asked for, and it wins
        // over the one on the account. Leaving it behind would show the screen
        // in the old language and make the change look as if it had not saved.
        $request->session()->put('locale', $validated['locale']);

        // The middleware settled the language before any of this ran, so the
        // message below would come out in the language being left behind.
        app()->setLocale($validated['locale']);

        return redirect()->route('settings.preferences.index')
            ->with('status', __('Your preferences are saved.'))
            ->with('status_description', __('They follow you to every device you sign in from.'));
    }
}
