{{--
  What somebody chose about the way the application reads to them: the language
  of the interface, the clock times are written on, and the order dates read in.

  Each row is its own form, and carries the other rows' current values along so
  that saving one never quietly resets the others. Which also means a row that
  saves has to refresh the rows carrying the value it just changed. The formats
  save over ajax; the language reloads the page, since every word on it is drawn
  in it, the two examples included.

  @var \App\ViewModels\Settings\Account\Preferences\PreferencesViewModel $viewModel
--}}
<x-app-layout :title="__('Preferences')">
  <x-slot:sidebar>
    <x-settings.sidebar :user="$viewModel->user()" current="preferences" />
  </x-slot:sidebar>

  <x-slot:breadcrumb>
    <nav class="text-sm text-muted" aria-label="{{ __('Breadcrumb') }}">
      {{ __('Settings') }}
      <span class="px-1 text-placeholder" aria-hidden="true">/</span>
      <span class="text-ink" aria-current="page">{{ __('Preferences') }}</span>
    </nav>
  </x-slot:breadcrumb>

  <x-page-header
    :title="__('Preferences')"
    :description="__('These settings apply to your account only, on every device you sign in from.')"
  />

  <div class="space-y-2">
    <x-box :title="__('General')" padding="p-0">
      <x-slot:help>
        <x-help :title="__('General')" align="right">
          {{ __('These are yours alone. Changing them here changes nothing for anybody else in your account.') }}
        </x-help>
      </x-slot:help>

      <x-box.row class="grid gap-x-6 gap-y-3 px-4.5 py-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
        <div class="min-w-0">
          <p class="text-sm font-medium text-ink">{{ __('Language') }}</p>
          <p class="mt-0.5 text-sm text-muted">{{ __('The language used across the :app interface.', ['app' => config('app.name')]) }}</p>
        </div>

        <x-form method="put" :action="route('settings.preferences.update')" id="language-form">
          <input type="hidden" name="time_format" value="{{ $viewModel->timeFormat()->value }}" />
          <input type="hidden" name="date_format" value="{{ $viewModel->dateFormat()?->value }}" />

          <x-dropdown
            name="locale"
            :label="$viewModel->localeLabel()"
            :options="$viewModel->locales()"
            :title="__('Interface language')"
          />
        </x-form>
      </x-box.row>

      <x-box.row class="grid gap-x-6 gap-y-3 px-4.5 py-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
        <div class="min-w-0">
          <p class="text-sm font-medium text-ink">{{ __('Time format') }}</p>
          <p class="mt-0.5 text-sm text-muted">{{ __('How times are written across the application.') }}</p>
        </div>

        {{--
          The other two menus are refreshed along with this one, since they carry
          the time format along and would otherwise send back the value being
          replaced.
        --}}
        <x-form
          method="put"
          :action="route('settings.preferences.update')"
          id="time-format-form"
          x-target="time-format-form language-form date-format-form preview"
          class="transition-opacity [&[aria-busy]]:opacity-60"
        >
          <input type="hidden" name="locale" value="{{ $viewModel->locale() }}" />
          <input type="hidden" name="date_format" value="{{ $viewModel->dateFormat()?->value }}" />

          <x-dropdown
            name="time_format"
            :label="$viewModel->timeFormatLabel()"
            :options="$viewModel->timeFormats()"
            :title="__('Time format')"
            monospaced-hints
          />
        </x-form>
      </x-box.row>

      <x-box.row class="grid gap-x-6 gap-y-3 px-4.5 py-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
        <div class="min-w-0">
          <p class="text-sm font-medium text-ink">{{ __('Date format') }}</p>
          <p class="mt-0.5 text-sm text-muted">{{ __('The order the parts of a date are written in.') }}</p>
        </div>

        <x-form
          method="put"
          :action="route('settings.preferences.update')"
          id="date-format-form"
          x-target="date-format-form language-form time-format-form preview"
          class="transition-opacity [&[aria-busy]]:opacity-60"
        >
          <input type="hidden" name="locale" value="{{ $viewModel->locale() }}" />
          <input type="hidden" name="time_format" value="{{ $viewModel->timeFormat()->value }}" />

          <x-dropdown
            name="date_format"
            :label="$viewModel->dateFormatLabel()"
            :options="$viewModel->dateFormats()"
            :title="__('Date format')"
            monospaced-hints
          />
        </x-form>
      </x-box.row>
    </x-box>

    <p id="preview" class="text-sm text-muted-soft">
      {{ __('It is :time on :date where you are, in :language.', ['time' => $viewModel->timePreview(), 'date' => $viewModel->datePreview(), 'language' => $viewModel->localeLabel()]) }}
    </p>
  </div>
</x-app-layout>
