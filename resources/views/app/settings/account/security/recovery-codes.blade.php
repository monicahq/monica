{{-- The one screen the recovery codes are printed on, straight after they are made. --}}
{{-- @var \App\ViewModels\Settings\Account\Security\RecoveryCodesViewModel $viewModel --}}
<x-settings.page
  :title="__('Your recovery codes')"
  :user="$viewModel->user()"
  current="security"
  :back-href="route('settings.security.index')"
  :back-label="__('Back to security')"
>
  <x-page-header :title="__('Your recovery codes')" :description="__('Each one signs you in once, if you ever lose the phone. This is the only time they are shown, so put them somewhere safe now.')" />

  <section class="flex flex-col gap-3">
    <x-panel>
      <div class="flex flex-col gap-4">
        <x-notice tone="warning" icon="reminder">{{ __('Leave this screen and they are gone. Ask for new ones if you lose them, which stops the ones above working.') }}</x-notice>

        <ul class="grid gap-1.5 rounded-md border border-hairline bg-sunken px-4 py-3.5 font-mono text-sm text-ink select-all sm:grid-cols-2">
          @foreach ($viewModel->codes() as $code)
            <li>{{ $code }}</li>
          @endforeach
        </ul>

        <div class="flex flex-wrap items-center gap-2.5">
          <x-copy-button
            :text="implode(PHP_EOL, $viewModel->codes())"
            :label="__('Copy to the clipboard')"
            :failure="__('We could not copy them. Select the codes and copy them by hand.')"
          />

          <x-button.secondary size="small" :href="route('settings.security.index')" data-turbo="true">{{ __('I have saved them') }}</x-button.secondary>
        </div>
      </div>
    </x-panel>
  </section>
</x-settings.page>
