{{-- The way to sign every device but this one out at once, behind the account password. --}}
<div class="flex flex-wrap items-center gap-3">
  <p class="min-w-0 flex-1 text-xs text-pretty text-muted">{{ __('Signed in somewhere you do not recognise? Sign out everywhere else at once.') }}</p>

  <x-modal.warning
    id="sign-out-other-devices"
    :title="__('Sign out every other device?')"
    :confirm="__('Sign out')"
    :cancel="__('Cancel')"
    :action="route('settings.otherSessions.destroy')"
  >
    <x-slot:trigger>
      <x-button variant="secondary" tone="danger" size="small" :href="null" type="button" @click="open = true">{{ __('Sign out everywhere else') }}</x-button>
    </x-slot:trigger>

    <p>{{ __('Every device but this one is asked to sign in at its next request, including the ones that stay signed in.') }}</p>

    <x-input
      type="password"
      id="other_sessions_password"
      name="password"
      form="sign-out-other-devices"
      :label="__('Your password')"
      autocomplete="current-password"
      allowPasswordManager
      required
    />
  </x-modal.warning>
</div>
