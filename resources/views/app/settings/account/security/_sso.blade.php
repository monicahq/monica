{{-- The provider half of the security screen: which one signs you in, and how to change that. --}}
{{-- @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel --}}
@if ($viewModel->connectedProvider() !== null)
  <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3.5">
    <div class="flex flex-col gap-1">
      <p class="text-sm font-medium text-ink">{{ $viewModel->connectedProvider()['name'] }}</p>

      @if ($viewModel->connectedProvider()['available'])
        <p class="text-sm text-muted">{{ __('Signs you in.') }}</p>
      @else
        <p class="text-sm text-muted">{{ __('This instance no longer offers it, so it cannot sign you in for now. Nothing was removed from your account.') }}</p>
      @endif
    </div>

    @if ($viewModel->canDisconnectTheProvider())
      <div x-data="{ confirming: false }">
        <x-button.secondary size="small" type="button" x-show="! confirming" @click="confirming = true">{{ __('Disconnect') }}</x-button.secondary>

        <div x-cloak x-show="confirming" class="flex flex-wrap items-center justify-end gap-2.5">
          <p class="text-xs text-error">{{ __('It stops signing you in, and you sign in with your password or a passkey from then on.') }}</p>

          <x-button.secondary size="small" type="button" @click="confirming = false">{{ __('Keep it') }}</x-button.secondary>

          <x-form method="delete" :action="route('settings.sso.destroy')">
            <x-button.danger size="small">{{ __('Disconnect') }}</x-button.danger>
          </x-form>
        </div>
      </div>
    @else
      <p class="text-sm text-muted">{{ __('Set a password or add a passkey before you disconnect it.') }}</p>
    @endif
  </div>
@else
  @forelse ($viewModel->providersToConnect() as $provider)
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3.5">
      <p class="text-sm font-medium text-ink">{{ $provider['name'] }}</p>

      <x-button.secondary :href="$provider['url']">{{ __('Connect') }}</x-button.secondary>
    </div>
  @empty
    @include('app.settings.account.security._sso-unavailable')
  @endforelse
@endif
