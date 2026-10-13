{{-- The flags this instance declares, and whether each is on for every account. --}}
{{-- @var \App\ViewModels\Admin\FlagsViewModel $viewModel --}}
<x-admin-layout :title="__('Flags')">
  <x-modal.confirm-password
    :offers-password="$viewModel->credentials()->offersPassword()"
    :offers-passkey="$viewModel->credentials()->offersPasskey()"
    :description="__('What you are about to change decides how this instance behaves for everybody, so we ask for a credential first. You will not be asked again for a little while.')"
  />

  <x-page-header :title="__('Flags')" :description="__('A flag keeps a capability that is still being built out of sight until it is switched on. Switch one for every account here, or for one account from its own screen.')" />

  <x-list-panel :items="$viewModel->flags()">
    @foreach ($viewModel->flags() as $flag)
      <div class="flex flex-col gap-2 border-b border-hairline px-4 py-3.5 last:border-b-0 sm:flex-row sm:items-center sm:gap-4">
        <p class="grow font-mono text-sm break-all text-ink">{{ $flag['name'] }}</p>

        @if ($flag['isOnForEveryAccount'])
          <x-badge tone="success">{{ __('On for every account') }}</x-badge>

          <x-modal.warning
            :title="__('Deactivate :flag for every account?', ['flag' => $flag['name']])"
            :confirm="__('Deactivate it')"
            :action="$flag['deactivateUrl']"
            confirms-password
          >
            <x-slot:trigger>
              <x-button.danger type="button" size="small" @click="open = true">{{ __('Deactivate for every account') }}<span class="sr-only"> {{ $flag['name'] }}</span></x-button.danger>
            </x-slot:trigger>

            {{ __('The capability disappears for every account from its next page, accounts created later included. Nothing anybody made with it is deleted, and activating it again brings it back.') }}
          </x-modal.warning>
        @else
          <x-badge>{{ __('Off for every account') }}</x-badge>

          <x-modal.warning
            :title="__('Activate :flag for every account?', ['flag' => $flag['name']])"
            :confirm="__('Activate it')"
            method="post"
            :action="$flag['activateUrl']"
            confirms-password
          >
            <x-slot:trigger>
              <x-button.secondary type="button" size="small" @click="open = true">{{ __('Activate for every account') }}<span class="sr-only"> {{ $flag['name'] }}</span></x-button.secondary>
            </x-slot:trigger>

            {{ __('The capability appears for every account from its next page, accounts created later included. A choice already made for one account is replaced.') }}
          </x-modal.warning>
        @endif
      </div>
    @endforeach

    <x-slot:empty>
      <x-empty-state :title="__('No flag is declared')">
        {{ __('Every capability of this instance is available to everybody. A flag appears here once the code declares one.') }}
      </x-empty-state>
    </x-slot:empty>
  </x-list-panel>
</x-admin-layout>
