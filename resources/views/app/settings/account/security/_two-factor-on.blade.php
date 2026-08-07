{{--
  What somebody sees once a code is asked for on top of their password: when it
  was turned on, the way back out, and the codes that get them in if they ever
  lose the phone.

  Each destructive button swaps itself for what is about to be lost and the
  button that goes through with it, so nothing here acts on the first click.

  @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel
--}}
<div class="space-y-6">
  <div class="grid gap-x-6 gap-y-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
    <div class="min-w-0 space-y-1">
      <p class="flex items-center gap-2 text-sm font-semibold text-ink">
        <span class="size-2 shrink-0 rounded-full bg-success" aria-hidden="true"></span>
        {{ __('On') }}
      </p>

      <p class="text-sm text-muted">{{ __('Turned on :time. We ask for a code every time you sign in.', ['time' => $viewModel->twoFactorConfirmedAt()]) }}</p>
    </div>

    <div x-data="{ confirming: false }">
      <x-button.secondary type="button" x-show="! confirming" @click="confirming = true" class="max-sm:w-full">{{ __('Turn it off') }}</x-button.secondary>

      <div x-cloak x-show="confirming" class="flex flex-wrap items-center gap-3 max-sm:flex-col max-sm:items-stretch">
        <p class="text-sm text-error max-sm:text-center">{{ __('Your password alone will get you in again. Sure?') }}</p>

        <x-button.secondary type="button" @click="confirming = false">{{ __('Keep it on') }}</x-button.secondary>

        <x-form method="delete" :action="route('settings.twoFactor.destroy')">
          <x-button class="bg-error hover:bg-error/88 max-sm:w-full">{{ __('Turn it off') }}</x-button>
        </x-form>
      </div>
    </div>
  </div>

  <div class="space-y-3 border-t border-hairline-soft pt-5">
    <div class="grid gap-x-6 gap-y-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
      <div class="min-w-0 space-y-1">
        <p class="text-sm font-semibold text-ink">{{ __('Recovery codes') }}</p>

        <p class="text-sm text-muted">{{ __('Keep these somewhere safe. Each one signs you in once, if you ever lose the phone.') }}</p>
      </div>

      <div x-data="{ confirming: false }">
        <x-button.secondary type="button" x-show="! confirming" @click="confirming = true" class="max-sm:w-full">{{ __('Get new codes') }}</x-button.secondary>

        <div x-cloak x-show="confirming" class="flex flex-wrap items-center gap-3 max-sm:flex-col max-sm:items-stretch">
          <p class="text-sm text-error max-sm:text-center">{{ __('The codes below stop working. Sure?') }}</p>

          <x-button.secondary type="button" @click="confirming = false">{{ __('Keep them') }}</x-button.secondary>

          <x-form method="post" :action="route('settings.recoveryCodes.create')">
            <x-button class="bg-error hover:bg-error/88 max-sm:w-full">{{ __('Get new codes') }}</x-button>
          </x-form>
        </div>
      </div>
    </div>

    @if ($viewModel->recoveryCodes() === [])
      <p class="text-sm text-muted-soft">{{ __('You have used every code. Ask for new ones before you need them.') }}</p>
    @else
      <ul class="grid gap-1.5 rounded-lg border border-hairline bg-sunken px-4 py-3.5 font-mono text-sm text-ink select-all sm:grid-cols-2">
        @foreach ($viewModel->recoveryCodes() as $code)
          <li>{{ $code }}</li>
        @endforeach
      </ul>
    @endif
  </div>
</div>
