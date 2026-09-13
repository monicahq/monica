<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Preferences;

use App\Actions\UpdatePreferences;
use App\Enums\DateFormatEnum;
use App\Enums\NumberFormatEnum;
use App\Enums\TimeFormatEnum;
use App\Helpers\LocaleResolution;
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
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(array_keys(config('monica.locales')))],
            'time_format' => ['required', 'string', Rule::enum(TimeFormatEnum::class)],
            'date_format' => ['nullable', 'string', Rule::enum(DateFormatEnum::class)],
            'number_format' => ['nullable', 'string', Rule::enum(NumberFormatEnum::class)],
            'uses_readable_addresses' => ['required', 'boolean'],
        ]);

        new UpdatePreferences(
            user: $request->user(),
            locale: $validated['locale'],
            timeFormat: TimeFormatEnum::from($validated['time_format']),
            dateFormat: DateFormatEnum::tryFrom($validated['date_format'] ?? ''),
            numberFormat: NumberFormatEnum::tryFrom($validated['number_format'] ?? ''),
            usesReadableAddresses: $request->boolean('uses_readable_addresses'),
        )->execute();

        LocaleResolution::remember($validated['locale']);

        return redirect()->route('settings.preferences.index')
            ->with('status', __('Your preferences are saved.'))
            ->with('status_description', __('They follow you to every device you sign in from.'));
    }
}
