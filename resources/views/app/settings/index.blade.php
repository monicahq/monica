{{-- The home of the settings: what belongs to you, what belongs to the account, and the way out. --}}
{{-- @var \App\ViewModels\Settings\SettingsViewModel $viewModel --}}
<x-shell :title="__('Settings')">
  <x-slot:breadcrumb>
    <x-button.bar :href="route('vaults.index')" data-turbo="true" size="small">
      <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />
      {{ __('Back to your vaults') }}
    </x-button.bar>
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-settings.sidebar :user="$viewModel->user()" current="home" />
  </x-slot:sidebar>

  <header class="flex flex-col items-center gap-2.5 text-center">
    <x-avatar :user="$viewModel->user()" :size="56" />

    <h1 class="mt-1.5 text-page-title text-ink">{{ __('Settings') }}</h1>

    <p class="max-w-[52ch] text-base text-pretty text-body">{{ $viewModel->description() }}</p>

    <p class="mt-0.5 text-xs text-pretty text-muted">{{ $viewModel->signedInAs() }}</p>
  </header>

  @foreach ($viewModel->sections() as $section)
    <section class="flex flex-col gap-3">
      @if ($section['title'])
        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
          <h2 class="shrink-0 text-section-title text-ink">{{ $section['title'] }}</h2>

          <p class="text-sm text-pretty text-muted">{{ $section['description'] }}</p>
        </div>
      @endif

      <x-panel :flush="true">
        @foreach ($section['rows'] as $row)
          <x-row
            :title="$row['title']"
            :description="$row['description']"
            :icon="$row['icon']"
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
