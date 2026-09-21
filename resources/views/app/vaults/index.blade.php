{{-- Where somebody lands after signing in: the compartments their account is divided into. --}}
{{-- @var \App\ViewModels\Vaults\VaultsViewModel $viewModel --}}
@php
  $ceiling = $viewModel->ceilingMessage();
@endphp
<x-account-layout :title="$viewModel->title()">
  <x-page-header :title="$viewModel->title()">
    <x-slot:actions>
      @if ($ceiling !== null)
        <x-button.secondary :href="route('settings.billing.index')" data-turbo="true" size="small">
          {{ __('Upgrade') }}
        </x-button.secondary>
      @endif

      <x-button
        :href="$ceiling === null ? $viewModel->createUrl() : null"
        type="button"
        :aria-disabled="$ceiling !== null ? 'true' : null"
        :aria-describedby="$ceiling !== null ? 'vault-ceiling' : null"
        data-turbo="true"
        size="small"
      >
        <x-slot:icon>
          <x-icon name="plus" :size="16" :stroke-width="1.6" />
        </x-slot:icon>

        {{ __('New vault') }}
      </x-button>
    </x-slot:actions>
  </x-page-header>

  @if ($ceiling !== null)
    <p id="vault-ceiling" class="sr-only">{{ $ceiling }}</p>
  @endif

  @if ($viewModel->vaults() === [])
    <x-panel>
      <x-empty-state :title="__('No vault yet')">
        <x-slot:icon>
          <x-icon name="vault" :size="30" :stroke-width="1.4" />
        </x-slot:icon>

        {{ __('A vault holds one part of your life. Everything you keep here is in a vault, and new records go to the first one you make.') }}

        <x-slot:action>
          <x-button
            :href="$ceiling === null ? $viewModel->createUrl() : null"
            type="button"
            :aria-disabled="$ceiling !== null ? 'true' : null"
            :aria-describedby="$ceiling !== null ? 'vault-ceiling' : null"
            data-turbo="true"
          >
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
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($viewModel->vaults() as $vault)
          <a
            href="{{ $vault['url'] }}"
            data-turbo="true"
            class="block overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel transition-colors duration-(--duration-fast) ease-out focus-ring"
          >
            <span class="block px-4 py-3.5">
              <span class="flex items-center gap-3">
                <span
                  class="flex size-9.5 shrink-0 items-center justify-center rounded-md text-sm font-semibold"
                  style="color: {{ $vault['colour'] }}; background: {{ $vault['tint'] }}"
                  aria-hidden="true"
                >{{ $vault['initial'] }}</span>

                <span class="min-w-0 flex-1">
                  <span class="block truncate text-base font-semibold text-ink">{{ $vault['name'] }}</span>

                  <span class="block text-xs text-muted">{{ $vault['visitLine'] }}</span>
                </span>

                @if ($vault['isDefault'])
                  <x-badge tone="info">{{ __('Default') }}</x-badge>
                @endif
              </span>

              <span class="mt-3.5 flex items-center gap-2">
                @forelse ($vault['faces'] as $face)
                  <x-contact-avatar :name="$face['name']" :url="$face['avatarUrl']" :size="30" />
                @empty
                  <span class="text-xs text-muted">{{ __('Nobody in it yet') }}</span>
                @endforelse

                @if ($vault['moreFaces'])
                  <span class="text-xs text-muted">{{ $vault['moreFaces'] }}</span>
                @endif
              </span>
            </span>

            <span class="grid grid-cols-2 border-t border-hairline bg-card">
              <span class="px-4 py-2.5">
                <span class="block text-base font-semibold text-ink">{{ $vault['openTaskCount'] }}</span>

                <x-caption>{{ __('Open tasks') }}</x-caption>
              </span>

              <span class="border-l border-hairline px-4 py-2.5">
                <span class="block text-base font-semibold text-ink">{{ $vault['dateCount'] }}</span>

                <x-caption>{{ __('Dates this month') }}</x-caption>
              </span>
            </span>
          </a>
        @endforeach

        <a
          href="{{ $viewModel->createUrl() }}"
          data-turbo="true"
          class="flex flex-col justify-center gap-2 rounded-lg border border-dashed border-hairline-strong p-5 transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring"
        >
          <span
            class="flex size-9 items-center justify-center rounded-md bg-card text-body"
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
