{{-- What somebody chose about how the application reads to them: language, clock, dates, numbers. --}}
{{-- @var \App\ViewModels\Settings\Account\Preferences\PreferencesViewModel $viewModel --}}
@php
  $rows = 'language-form time-format-form date-format-form number-format-form preview timezone-panel';
@endphp
<x-shell :title="__('Preferences')">
  <x-slot:breadcrumb>
    <x-button.bar :href="route('settings.index')" data-turbo="true" size="small">
      <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />
      {{ __('Back to settings') }}
    </x-button.bar>
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-settings.sidebar :user="$viewModel->user()" current="preferences" />
  </x-slot:sidebar>

  <header class="flex flex-col gap-2">
    <h1 class="text-page-title text-ink">{{ __('Preferences') }}</h1>

    <p class="max-w-[56ch] text-base text-pretty text-body">{{ __('These are yours alone, on every device you sign in from. Nothing here changes for anybody else in your account.') }}</p>
  </header>

  <section class="flex flex-col gap-3">
    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
      <h2 class="shrink-0 text-section-title text-ink">{{ __('Language and formats') }}</h2>

      <p class="text-sm text-pretty text-muted">{{ __('Each one saves as soon as you pick it. There is nothing to press.') }}</p>
    </div>

    <x-panel :flush="true">
      <x-row :title="__('Language')" :description="__('The language the application is shown in.')" icon="info">
        <x-slot:end>
          <x-form method="put" :action="route('settings.preferences.update')" id="language-form">
            @include('app.settings.account.preferences._carried-values', ['owns' => 'locale'])

            <x-dropdown
              name="locale"
              :label="$viewModel->localeLabel()"
              :options="$viewModel->locales()"
              :title="__('Interface language')"
            />
          </x-form>
        </x-slot:end>
      </x-row>

      <x-row :title="__('Time format')" :description="__('How a time of day is written.')" icon="calendar">
        <x-slot:end>
          <x-form
            method="put"
            :action="route('settings.preferences.update')"
            id="time-format-form"
            x-target="{{ $rows }}"
            class="transition-opacity [&[aria-busy]]:opacity-60"
          >
            @include('app.settings.account.preferences._carried-values', ['owns' => 'time_format'])

            <x-dropdown
              name="time_format"
              :label="$viewModel->timeFormatLabel()"
              :options="$viewModel->timeFormats()"
              :title="__('Time format')"
              monospaced-hints
            />
          </x-form>
        </x-slot:end>
      </x-row>

      <x-row :title="__('Date format')" :description="__('The order the parts of a date are written in.')" icon="calendar">
        <x-slot:end>
          <x-form
            method="put"
            :action="route('settings.preferences.update')"
            id="date-format-form"
            x-target="{{ $rows }}"
            class="transition-opacity [&[aria-busy]]:opacity-60"
          >
            @include('app.settings.account.preferences._carried-values', ['owns' => 'date_format'])

            <x-dropdown
              name="date_format"
              :label="$viewModel->dateFormatLabel()"
              :options="$viewModel->dateFormats()"
              :title="__('Date format')"
              monospaced-hints
            />
          </x-form>
        </x-slot:end>
      </x-row>

      <x-row :title="__('Number format')" :description="__('The separators between the thousands and before the decimals.')" icon="tasks">
        <x-slot:end>
          <x-form
            method="put"
            :action="route('settings.preferences.update')"
            id="number-format-form"
            x-target="{{ $rows }}"
            class="transition-opacity [&[aria-busy]]:opacity-60"
          >
            @include('app.settings.account.preferences._carried-values', ['owns' => 'number_format'])

            <x-dropdown
              name="number_format"
              :label="$viewModel->numberFormatLabel()"
              :options="$viewModel->numberFormats()"
              :title="__('Number format')"
              monospaced-hints
            />
          </x-form>
        </x-slot:end>
      </x-row>

      <x-slot:footer id="preview">
        {{ __('It is :time on :date where you are, in :language.', ['time' => $viewModel->timePreview(), 'date' => $viewModel->datePreview(), 'language' => $viewModel->localeLabel()]) }}
      </x-slot:footer>
    </x-panel>
  </section>

  @include('app.settings.account.preferences._timezone', ['timezone' => $viewModel->timezone(session('timezone_guess', false))])
</x-shell>
