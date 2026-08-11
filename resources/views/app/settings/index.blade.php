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
    <a href="{{ route('vaults.index') }}" data-turbo="true" class="erica-btn erica-btn--secondary erica-btn--sm">
      <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />
      {{ __('Back to your vaults') }}
    </a>
  </x-slot:bar>

  <x-slot:dock>
    <x-dock :user="$viewModel->user()" />
  </x-slot:dock>

  <header class="flex flex-col items-center gap-2.5 text-center">
    <x-avatar :user="$viewModel->user()" :size="56" />

    <h1 class="mt-1.5 erica-page-title">{{ __('Settings') }}</h1>

    <p class="erica-lede max-w-[52ch]">{{ $viewModel->description() }}</p>

    <p class="erica-caption mt-0.5">{{ $viewModel->signedInAs() }}</p>
  </header>

  @foreach ($viewModel->sections() as $section)
    <section class="flex flex-col gap-3">
      @if ($section['title'])
        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
          <h2 class="erica-section-title shrink-0">{{ $section['title'] }}</h2>

          <p class="erica-small">{{ $section['description'] }}</p>
        </div>
      @endif

      <x-panel :flush="true">
        @foreach ($section['rows'] as $row)
          <x-row
            :title="$row['title']"
            :description="$row['description']"
            :icon="$row['icon']"
            :icon-color="$row['hue']"
            :icon-background="$row['tint']"
            :meta="$row['meta']"
            :href="isset($row['method']) ? null : $row['url']"
          >
            <x-slot:end>
              @if (isset($row['method']))
                <x-form :method="$row['method']" :action="$row['url']">
                  <button type="submit" class="erica-btn erica-btn--secondary erica-btn--sm">
                    {{ $row['title'] }}
                  </button>
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
        <p class="erica-caption px-0.5">
          {{ __('Deleting the account removes every vault, every person in them and every note you have written. It cannot be undone.') }}

          <a href="{{ $viewModel->deleteUrl() }}" data-turbo="true" style="color: var(--danger)">
            {{ __('Delete this account') }}
          </a>
        </p>
      @endif
    </section>
  @endforeach
</x-shell>
