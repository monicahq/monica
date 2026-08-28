{{--
  What somebody sees once a code is asked for on top of their password: that it is
  on, the way back out, and the codes that get them in if they ever lose the phone.

  Each destructive button swaps itself for what is about to be lost and the button
  that goes through with it, so nothing here acts on the first click.

  The codes sit in their own row rather than in the one above, because they are a
  thing you keep rather than a setting you changed.

  @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel
--}}
<x-row
  :title="__('Authenticator app')"
  :description="__('Turned on :time. We ask for a code every time you sign in.', ['time' => $viewModel->twoFactorConfirmedAt()])"
  icon="lock"
>
  <x-slot:end>
    <x-badge tone="success" icon="check">{{ __('On') }}</x-badge>

    <div x-data="{ confirming: false }">
      <x-button.secondary size="small" type="button" x-show="! confirming" @click="confirming = true">{{ __('Turn it off') }}</x-button.secondary>

      <div x-cloak x-show="confirming" class="flex flex-wrap items-center justify-end gap-2.5">
        <p class="text-xs text-error">{{ __('Your password alone will get you in again. Sure?') }}</p>

        <x-button.secondary size="small" type="button" @click="confirming = false">{{ __('Keep it on') }}</x-button.secondary>

        <x-form method="delete" :action="route('settings.twoFactor.destroy')">
          <x-button.danger size="small">{{ __('Turn it off') }}</x-button.danger>
        </x-form>
      </div>
    </div>
  </x-slot:end>
</x-row>

<x-row
  :title="__('Recovery codes')"
  :description="__('Keep these somewhere safe. Each one signs you in once, if you ever lose the phone.')"
  icon="key"
>
  <x-slot:end>
    <div x-data="{ confirming: false }">
      <x-button.secondary size="small" type="button" x-show="! confirming" @click="confirming = true">{{ __('Get new codes') }}</x-button.secondary>

      <div x-cloak x-show="confirming" class="flex flex-wrap items-center justify-end gap-2.5">
        <p class="text-xs text-error">{{ __('The codes below stop working. Sure?') }}</p>

        <x-button.secondary size="small" type="button" @click="confirming = false">{{ __('Keep them') }}</x-button.secondary>

        <x-form method="post" :action="route('settings.recoveryCodes.create')">
          <x-button.danger size="small">{{ __('Get new codes') }}</x-button.danger>
        </x-form>
      </div>
    </div>
  </x-slot:end>
</x-row>

<div class="px-4 py-3.5">
  @if ($viewModel->recoveryCodes() === [])
    <x-notice tone="warning">{{ __('You have used every code. Ask for new ones before you need them.') }}</x-notice>
  @else
    <ul class="grid gap-1.5 rounded-md border border-hairline bg-sunken px-4 py-3.5 font-mono sm:grid-cols-2 text-sm text-ink select-all">
      @foreach ($viewModel->recoveryCodes() as $code)
        <li>{{ $code }}</li>
      @endforeach
    </ul>
  @endif
</div>
