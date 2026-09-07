{{-- What somebody sees once a code is asked for on top of their password. --}}
{{-- @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel --}}
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
        <p class="text-xs text-error">{{ __('Your password alone will sign you in again, with no code asked for.') }}</p>

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
        <p class="text-xs text-error">{{ __('The codes below stop working, and cannot be used again.') }}</p>

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

@if ($viewModel->recoveryCodes() !== [])
  <div
    class="flex flex-wrap items-center gap-2.5 px-4 pbe-3.5"
    x-data="{
      state: 'idle',
      messages: {
        idle: '',
        copied: @js(__('Copied')),
        failed: @js(__('We could not copy it. Select the key and copy it by hand.')),
      },
      codes: @js($viewModel->recoveryCodes()),
    }"
  >
    <x-button
      type="button"
      size="small"
      @click="window.copyText(codes.join('\n')).then((ok) => { state = ok ? 'copied' : 'failed'; if (ok) setTimeout(() => { if (state === 'copied') state = 'idle' }, 2500) })"
    >{{ __('Copy to the clipboard') }}</x-button>

    <p
      role="status"
      aria-live="polite"
      class="text-sm"
      :class="{ 'text-success': state === 'copied', 'text-error': state === 'failed' }"
      x-text="messages[state] ?? ''"
    ></p>
  </div>
@endif

