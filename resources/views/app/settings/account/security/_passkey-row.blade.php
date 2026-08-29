{{--
  One passkey, with the two things somebody can do to it.

  A passkey nobody has used yet says so, rather than leaving the line blank,
  because "not used yet" and "we lost track of it" are different things and only
  one of them is true.

  Removing one is destructive and irreversible: the device holding it cannot be
  made to sign in again, and a new passkey has to be created on it instead. So
  the button asks first, and says what the removal costs before it happens.

  @var array{id: int, name: string, addedAt: string, lastUsedAt: ?string} $passkey
--}}
<x-row
  :title="$passkey['name']"
  :description="$passkey['lastUsedAt']
    ? __('Added :added · last used :used', ['added' => $passkey['addedAt'], 'used' => $passkey['lastUsedAt']])
    : __('Added :added · never used yet', ['added' => $passkey['addedAt']])"
  icon="key"
  icon-class="bg-success-tint text-success"
>
  <x-slot:end>
    <div x-data="{ renaming: false, confirming: false }" class="flex flex-wrap items-center justify-end gap-2.5">
      <x-button.secondary
        size="small"
        type="button"
        x-show="! renaming && ! confirming"
        @click="renaming = true; $nextTick(() => $refs.name.focus())"
      >{{ __('Rename') }}</x-button.secondary>

      <x-form
        method="put"
        :action="route('settings.passkeys.update', $passkey['id'])"
        x-cloak
        x-show="renaming"
        class="flex flex-wrap items-center justify-end gap-2.5"
      >
        <x-input
          x-ref="name"
          :id="'passkey-name-'.$passkey['id']"
          name="name"
          :label="__('Name')"
          :value="$passkey['name']"
          size="small"
          class="w-44"
        />

        <x-button.secondary size="small" type="button" @click="renaming = false">{{ __('Cancel') }}</x-button.secondary>

        <x-button size="small">{{ __('Save') }}</x-button>
      </x-form>

      <x-button.secondary
        size="small"
        type="button"
        x-show="! renaming && ! confirming"
        @click="confirming = true"
      >{{ __('Remove') }}</x-button.secondary>

      <div x-cloak x-show="confirming" class="flex flex-wrap items-center justify-end gap-2.5">
        @if ($lastOneOnAPasswordlessAccount ?? false)
          {{--
            FR-033. Removing this passkey is not refused, because refusing would
            leave somebody holding a passkey they cannot revoke. It is said out
            loud instead, and setting a password is offered as the alternative.
          --}}
          <p class="text-xs text-error text-pretty">{{ __('This is your last passkey and you have no password. A link sent to your email address would become the only way in. Set a password instead?') }}</p>
        @else
          <p class="text-xs text-error">{{ __('This device will no longer sign you in. Sure?') }}</p>
        @endif

        <x-button.secondary size="small" type="button" @click="confirming = false">{{ __('Keep it') }}</x-button.secondary>

        <x-form method="delete" :action="route('settings.passkeys.destroy', $passkey['id'])">
          <x-button.danger size="small">{{ __('Remove') }}</x-button.danger>
        </x-form>
      </div>
    </div>
  </x-slot:end>
</x-row>
