{{--
  The home of the settings: what belongs to you, what belongs to the account, and
  the two ways of leaving.

  It is a list of rows rather than a grid of cards on purpose. Every row is one
  sentence about what is behind it, which is what somebody arriving here actually
  needs. The names alone ("Templates", "Entity types") say almost nothing about which
  one to open.

  Deleting the account is the last thing on the page and it is not a row. It is a
  sentence that says what goes with it, and the words that do it are the last three
  of that sentence, so nobody reaches them without having read the rest. The
  confirmation itself lives on the profile screen and asks for the address to be
  typed out.

  @var \App\ViewModels\Settings\SettingsViewModel $viewModel
--}}
<x-shell :title="__('Settings')">
  <x-slot:bar>
    <x-button.bar :href="route('vaults.index')" data-turbo="true" size="small">
      <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />
      {{ __('Back to your vaults') }}
    </x-button.bar>
  </x-slot:bar>

  <x-slot:dock>
    <x-dock :user="$viewModel->user()" />
  </x-slot:dock>

  <header class="flex flex-col items-center gap-2.5 text-center">
    <x-avatar :user="$viewModel->user()" :size="56" />

    <h1 class="mt-1.5 font-display text-page-title text-ink">{{ __('Settings') }}</h1>

    <p class="max-w-[52ch] text-base text-pretty text-body">{{ $viewModel->description() }}</p>

    <p class="mt-0.5 text-xs text-pretty text-muted">{{ $viewModel->signedInAs() }}</p>
  </header>

  @foreach ($viewModel->sections() as $section)
    <section class="flex flex-col gap-3">
      @if ($section['title'])
        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
          <h2 class="shrink-0 font-display text-section-title text-ink">{{ $section['title'] }}</h2>

          <p class="text-sm text-pretty text-muted">{{ $section['description'] }}</p>
        </div>
      @endif

      <x-panel :flush="true">
        @foreach ($section['rows'] as $row)
          <x-row
            :title="$row['title']"
            :description="$row['description']"
            :icon="$row['icon']"
            :icon-class="$row['iconClass']"
            :meta="$row['meta']"
            :href="isset($row['method']) ? null : $row['url']"
          >
            <x-slot:end>
              @if (isset($row['method']))
                <x-form :method="$row['method']" :action="$row['url']">
                  <x-button.secondary size="small">
                    {{ $row['title'] }}
                  </x-button.secondary>
                </x-form>
              @elseif ($row['url'])
                <x-icon name="chevron-right" :size="16" :stroke-width="1.6" class="text-muted" />
              @endif
            </x-slot:end>
          </x-row>
        @endforeach
      </x-panel>

      {{-- The only thing on this screen that cannot be undone, under the rows that can. --}}
      @if ($loop->last)
        <p class="px-0.5 text-xs text-pretty text-muted">
          {{ __('Deleting the account removes every vault, every person in them and every note you have written. It cannot be undone.') }}

          <a href="{{ $viewModel->deleteUrl() }}" data-turbo="true" class="text-error">
            {{ __('Delete this account') }}
          </a>
        </p>
      @endif
    </section>
  @endforeach
</x-shell>
