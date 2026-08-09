{{--
  The list of compartments the account is divided into.

  It is a placeholder, standing in until the list has a design of its own: a
  name, what the vault is for, and how much it holds. Nothing on a row leads
  anywhere yet, because there is no screen for one vault to lead to.

  @var \App\ViewModels\Vaults\VaultsViewModel $viewModel
--}}
<x-vault-layout :title="$viewModel->title()">
  <div class="flex flex-wrap items-end justify-between gap-4">
    <x-page-header :title="$viewModel->title()" :description="$viewModel->description()" />

    <x-button :href="$viewModel->createUrl()" data-turbo="true">{{ __('Create a vault') }}</x-button>
  </div>

  <x-box padding="p-0">
    @forelse ($viewModel->vaults() as $vault)
      <div class="flex items-start gap-3 px-4 py-3.5 not-first:border-t not-first:border-hairline-soft">
        <span style="background: {{ $vault['colour'] }}" class="mt-1 size-3 shrink-0 rounded-sm" aria-hidden="true"></span>

        <div class="min-w-0 flex-1 space-y-0.5">
          <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-ink">
            {{ $vault['name'] }}

            @if ($vault['isDefault'])
              <span class="rounded-sm bg-sunken px-1.5 py-0.5 text-xs font-medium text-muted">{{ __('Default') }}</span>
            @endif
          </p>

          @if ($vault['description'])
            <p class="text-[13px] leading-relaxed text-pretty text-muted">{{ $vault['description'] }}</p>
          @endif
        </div>

        <span class="mt-px shrink-0 text-xs text-muted-soft">{{ $vault['contactCount'] }}</span>
      </div>
    @empty
      <x-empty-state :title="__('No vault yet')">
        <x-slot:icon>
          <svg width="19" height="19" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.3" aria-hidden="true">
            <rect x="2.4" y="3.4" width="11.2" height="9.2" rx="1.6"></rect>
            <circle cx="8" cy="8" r="2.1"></circle>
          </svg>
        </x-slot:icon>

        {{ __('A vault is a sealed compartment for one part of your life. Everything you keep here lives in one.') }}
      </x-empty-state>
    @endforelse
  </x-box>
</x-vault-layout>
