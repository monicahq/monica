{{--
  The screen somebody lands on after signing in: the compartments their account is
  divided into, and the way into each.

  It is the one screen with no dock. The dock's four destinations point into a
  single vault, and this is where you choose which, so a dock here would be
  pointing somewhere before the choice had been made. That also means it is the
  one screen that needs its own way to the settings, which is the link in the bar.

  The cards are cards rather than rows, which is the only place in the product they
  are. A vault is not one of a long list to be scanned down a column: there are
  three or four of them, they are picked by recognition, and each carries a
  sentence about what it is for that a row would have to truncate.

  @var \App\ViewModels\Vaults\VaultsViewModel $viewModel
--}}
<x-account-layout :title="$viewModel->title()">
  <header class="flex flex-wrap items-start justify-between gap-x-6 gap-y-4">
    <div class="flex min-w-0 flex-col gap-2">
      <h1 class="font-display text-page-title text-ink">{{ $viewModel->title() }}</h1>

      <p class="max-w-[64ch] text-base text-pretty text-body">{{ $viewModel->description() }}</p>
    </div>

    <div class="flex flex-wrap items-center gap-2.5 max-sm:w-full">
      <x-button :href="$viewModel->createUrl()" data-turbo="true" size="small">
        <x-slot:icon>
          <x-icon name="plus" :size="16" :stroke-width="1.6" />
        </x-slot:icon>

        {{ __('New vault') }}
      </x-button>
    </div>
  </header>

  {{--
    An account with no vault is normally sent to the getting started screen, so this
    is reached by typing the address. It still has to read sensibly: there is no
    order to explain and nothing to start "another" of.
  --}}
  @if ($viewModel->vaults() === [])
    <x-panel>
      <x-empty-state :title="__('No vault yet')">
        <x-slot:icon>
          <x-icon name="vault" :size="30" :stroke-width="1.4" />
        </x-slot:icon>

        {{ __('A vault is a sealed compartment for one part of your life. Everything you keep here lives in one, and the first one you make is where things land.') }}

        <x-slot:action>
          <x-button :href="$viewModel->createUrl()" data-turbo="true">
            <x-slot:icon>
              <x-icon name="plus" :size="18" :stroke-width="1.6" />
            </x-slot:icon>

            {{ __('Make the first one') }}
          </x-button>
        </x-slot:action>
      </x-empty-state>
    </x-panel>
  @else
    <section class="flex flex-col gap-3.5">
      <div class="flex flex-wrap items-baseline gap-x-4 gap-y-1 px-0.5">
        <h2 class="shrink-0 font-display text-section-title text-ink">{{ __('Vaults you keep') }}</h2>

        <p class="text-sm text-pretty text-muted">{{ $viewModel->orderNote() }}</p>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($viewModel->vaults() as $vault)
          <a
            href="{{ $vault['url'] }}"
            data-turbo="true"
            class="overflow-hidden rounded-lg border border-hairline bg-canvas flex flex-col gap-3.5 p-5 transition-colors duration-[110ms] hover:bg-hover"
          >
            <span class="flex items-center gap-3">
              <span
                class="flex size-10 shrink-0 items-center justify-center rounded-md text-base font-bold"
                style="color: {{ $vault['colour'] }}; background: {{ $vault['tint'] }}"
                aria-hidden="true"
              >{{ $vault['initial'] }}</span>

              <span class="min-w-0 flex-1">
                <span class="block truncate text-card-title text-ink">{{ $vault['name'] }}</span>

                <span class="block text-xs text-pretty text-muted">{{ $vault['contactCount'] }}</span>
              </span>

              @if ($vault['isLastOpened'])
                <x-badge tone="accent">{{ __('Last opened') }}</x-badge>
              @elseif ($vault['isDefault'])
                <x-badge>{{ __('Default') }}</x-badge>
              @endif
            </span>

            {{-- The sentence keeps its room whether it is there or not, so a card
                 without one does not stand shorter than the cards beside it. --}}
            <span class="block text-sm text-pretty text-muted min-h-10">{{ $vault['description'] }}</span>
          </a>
        @endforeach

        <a
          href="{{ $viewModel->createUrl() }}"
          data-turbo="true"
          class="flex flex-col justify-center gap-2 rounded-lg border border-dashed border-hairline-strong p-5 transition-colors duration-[110ms] hover:bg-hover"
        >
          <span
            class="flex size-9 items-center justify-center rounded-md bg-terracotta-tint text-terracotta"
            aria-hidden="true"
          >
            <x-icon name="plus" :size="19" :stroke-width="1.6" />
          </span>

          <span class="block text-base font-semibold text-ink">{{ __('Start another vault') }}</span>

          <span class="block text-sm text-pretty text-muted">{{ __('Work, a club, the family on the other side. Nothing in it is visible from here.') }}</span>
        </a>
      </div>
    </section>
  @endif
</x-account-layout>
