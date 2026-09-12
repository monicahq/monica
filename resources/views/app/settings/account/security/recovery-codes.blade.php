{{-- The one screen the recovery codes are printed on, straight after they are made. --}}
{{-- @var \App\ViewModels\Settings\Account\Security\RecoveryCodesViewModel $viewModel --}}
<x-shell :title="__('Your recovery codes')">
  <x-slot:breadcrumb>
    <x-button.bar :href="route('settings.security.index')" data-turbo="true" size="small">
      <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />
      {{ __('Back to security') }}
    </x-button.bar>
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-settings.sidebar :user="$viewModel->user()" current="security" />
  </x-slot:sidebar>

  <header class="flex flex-col gap-2">
    <h1 class="text-page-title text-ink">{{ __('Your recovery codes') }}</h1>

    <p class="max-w-[56ch] text-base text-pretty text-body">{{ __('Each one signs you in once, if you ever lose the phone. This is the only time they are shown, so put them somewhere safe now.') }}</p>
  </header>

  <section class="flex flex-col gap-3">
    <x-panel>
      <div
        class="flex flex-col gap-4"
        x-data="{
          state: 'idle',
          messages: {
            idle: '',
            copied: @js(__('Copied')),
            failed: @js(__('We could not copy them. Select the codes and copy them by hand.')),
          },
          codes: @js($viewModel->codes()),
        }"
      >
        <x-notice tone="warning" icon="reminder">{{ __('Leave this screen and they are gone. Ask for new ones if you lose them, which stops the ones above working.') }}</x-notice>

        <ul class="grid gap-1.5 rounded-md border border-hairline bg-sunken px-4 py-3.5 font-mono text-sm text-ink select-all sm:grid-cols-2">
          @foreach ($viewModel->codes() as $code)
            <li>{{ $code }}</li>
          @endforeach
        </ul>

        <div class="flex flex-wrap items-center gap-2.5">
          <x-button
            type="button"
            size="small"
            @click="window.copyText(codes.join('\n')).then((ok) => { state = ok ? 'copied' : 'failed'; if (ok) setTimeout(() => { if (state === 'copied') state = 'idle' }, 2500) })"
          >{{ __('Copy to the clipboard') }}</x-button>

          <x-button.secondary size="small" :href="route('settings.security.index')" data-turbo="true">{{ __('I have saved them') }}</x-button.secondary>

          <p
            role="status"
            aria-live="polite"
            class="text-sm"
            :class="{ 'text-success': state === 'copied', 'text-error': state === 'failed' }"
            x-text="messages[state] ?? ''"
          ></p>
        </div>
      </div>
    </x-panel>
  </section>
</x-shell>
