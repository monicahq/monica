{{--
  What somebody sees when a code is not asked for on top of their password.

  The screen it leads to is built on this same layout, so it is handed to turbo
  and only the body changes hands.
--}}
<div class="grid gap-x-6 gap-y-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
  <div class="flex items-center gap-x-4">
    <svg width="20" height="20" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 text-placeholder" aria-hidden="true">
      <rect x="4.4" y="1.6" width="7.2" height="12.8" rx="1.8"></rect>
      <line x1="6.9" y1="12.2" x2="9.1" y2="12.2"></line>
    </svg>

    <div class="min-w-0 space-y-1">
      <p class="text-sm font-semibold text-ink">{{ __('Authenticator app') }}</p>

      <p class="text-sm text-muted">{{ __('Ask us for a code from your authenticator app every time you sign in, on top of your password.') }}</p>

      <p class="text-sm text-muted">{{ __('You will need an authenticator app on your phone. Any of them works.') }}</p>
    </div>
  </div>

  <x-button :href="route('settings.twoFactor.new')" data-turbo="true" class="max-sm:w-full">{{ __('Turn it on') }}</x-button>
</div>
