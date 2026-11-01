{{-- The recovery codes standing in for a lost phone or key, while two-factor authentication is on. --}}
{{-- @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel --}}
<x-row
  :title="__('Recovery codes')"
  :description="__('Keep these somewhere safe. Each one signs you in once, if you ever lose your phone or your key.')"
  icon="key"
>
  <x-slot:end>
    <div x-data="{ confirming: false }">
      <x-button.secondary size="small" type="button" x-show="! confirming" @click="confirming = true">{{ __('Get new codes') }}</x-button.secondary>

      <div x-cloak x-show="confirming" class="flex flex-wrap items-center justify-end gap-2.5">
        <p class="text-xs text-error">{{ __('The codes below stop working, and cannot be used again.') }}</p>

        <x-button.secondary size="small" type="button" @click="confirming = false">{{ __('Keep them') }}</x-button.secondary>

        <x-form method="post" :action="route('settings.recoveryCodes.create')">
          <x-button.danger size="small" type="submit" @click.prevent="$dispatch('confirm-password', { form: $el.form })">{{ __('Get new codes') }}</x-button.danger>
        </x-form>
      </div>
    </div>
  </x-slot:end>
</x-row>

<div class="px-4 py-3.5">
  @if ($viewModel->recoveryCodesRemaining() === 0)
    <x-notice tone="warning">{{ __('You have used every code. Ask for new ones before you need them.') }}</x-notice>
  @else
    <p class="text-sm text-body">{{ trans_choice(':count recovery code left. They were shown once, when they were made.|:count recovery codes left. They were shown once, when they were made.', $viewModel->recoveryCodesRemaining()) }}</p>
  @endif
</div>
