{{-- One security key, with the two things somebody can do to it. --}}
{{--
  @var array{id: int, name: string, addedAt: string, lastUsedAt: ?string} $securityKey
  @var string $warning
  @var bool $asksForConfirmation
--}}
<x-row
  :title="$securityKey['name']"
  :description="$securityKey['lastUsedAt']
    ? __('Added :added · last used :used', ['added' => $securityKey['addedAt'], 'used' => $securityKey['lastUsedAt']])
    : __('Added :added · never used yet', ['added' => $securityKey['addedAt']])"
  icon="key"
  icon-class="bg-success-tint text-success"
>
  <x-slot:end>
    <div x-data="{ renaming: false, confirming: false, asksForConfirmation: @js($asksForConfirmation) }" class="flex flex-wrap items-center justify-end gap-2.5">
      <x-button.secondary
        size="small"
        type="button"
        x-show="! renaming && ! confirming"
        @click="renaming = true; $nextTick(() => $refs.name.focus())"
      >{{ __('Rename') }}</x-button.secondary>

      <x-form
        method="put"
        :action="route('settings.securityKeys.update', $securityKey['id'])"
        x-cloak
        x-show="renaming"
        class="flex flex-wrap items-center justify-end gap-2.5"
      >
        <x-input
          x-ref="name"
          :id="'security-key-name-'.$securityKey['id']"
          name="name"
          :label="__('Name')"
          :value="$securityKey['name']"
          size="small"
          class="w-44"
        />

        <x-button.secondary size="small" type="button" @click="renaming = false">{{ __('Cancel') }}</x-button.secondary>

        <x-button size="small" type="submit" @click.prevent="asksForConfirmation ? $dispatch('confirm-password', { form: $el.form }) : $el.form.requestSubmit()">{{ __('Save') }}</x-button>
      </x-form>

      <x-button.secondary
        size="small"
        type="button"
        x-ref="remove"
        x-show="! renaming && ! confirming"
        @click="confirming = true; $nextTick(() => $refs.keep.focus())"
      >{{ __('Remove') }}</x-button.secondary>

      <div x-cloak x-show="confirming" class="flex flex-wrap items-center justify-end gap-2.5">
        <p id="security-key-warning-{{ $securityKey['id'] }}" class="text-xs text-error text-pretty">{{ $warning }}</p>

        <x-button.secondary size="small" type="button" x-ref="keep" @click="confirming = false; $nextTick(() => $refs.remove.focus())">{{ __('Keep it') }}</x-button.secondary>

        <x-form method="delete" :action="route('settings.securityKeys.destroy', $securityKey['id'])">
          <x-button.danger size="small" type="submit" aria-describedby="security-key-warning-{{ $securityKey['id'] }}" @click.prevent="asksForConfirmation ? $dispatch('confirm-password', { form: $el.form }) : $el.form.requestSubmit()">{{ __('Remove') }}</x-button.danger>
        </x-form>
      </div>
    </div>
  </x-slot:end>
</x-row>
