{{--
  What somebody chose about the way the application reads to them: the language
  of the interface, the clock times are written on, the order dates read in, and
  the separators numbers are written with.

  Four rows, and each one is its own form: there is no Save on this screen because
  picking a value is the save. That is the one place the system allows a control to
  act on being touched, and it is allowed here because every one of these is
  reversible by picking again.

  Each row carries the other rows' current values along so that saving one never
  quietly resets the others, which the partial below does for all of them. Which
  also means a row that saves has to refresh the rows carrying the value it just
  changed, and by now that is every other row. The formats save over ajax; the
  language reloads the page, since every word on it is drawn in it, the examples
  included.

  @var \App\ViewModels\Settings\Account\Preferences\PreferencesViewModel $viewModel
--}}
@php
  // The time zone panel shows a clock, so a change to the clock's format has to
  // redraw it too, which is why it is in the set every format row refreshes.
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
      <x-row :title="__('Language')" :description="__('The language the interface is drawn in.')" icon="info">
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

      {{--
        What the four choices add up to, in one sentence. It is the point of the
        screen: nobody picks a date format for its name, they pick it for what a
        date then looks like.
      --}}
      <x-slot:footer id="preview">
        {{ __('It is :time on :date where you are, in :language.', ['time' => $viewModel->timePreview(), 'date' => $viewModel->datePreview(), 'language' => $viewModel->localeLabel()]) }}
      </x-slot:footer>
    </x-panel>
  </section>

  @include('app.settings.account.preferences._timezone', ['timezone' => $viewModel->timezone(session('timezone_guess', false))])
</x-shell>
