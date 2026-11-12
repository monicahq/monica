{{-- One possession tag on the list, which turns into its own form to be renamed or recoloured. --}}
{{--
  @var array{uuid: string, name: string, colour: string, colourName: string, deleteConsequence: string, updateAction: string, destroyAction: string} $tag
  @var list<array{value: string, label: string, isDefault: bool}> $colours
--}}
@php
  $tagErrors = $errors->getBag('possessionTag'.$tag['uuid']);
@endphp
<li
  x-data="{ editing: @js($tagErrors->any()) }"
  @keydown.escape="if (editing) { editing = false; $nextTick(() => $refs.edit.focus()) }"
  class="border-b border-hairline last:border-b-0"
>
  <div x-show="! editing" class="flex items-center gap-3 px-4 py-3">
    <span class="flex size-8 flex-none items-center justify-center rounded-md" style="background: color-mix(in srgb, {{ $tag['colour'] }} 14%, var(--canvas))" aria-hidden="true">
      <span class="size-2.5 rounded-full" style="background: {{ $tag['colour'] }}"></span>
    </span>

    <span class="min-w-0 flex-1">
      <span class="block truncate font-semibold text-ink">{{ $tag['name'] }}</span>
      <span class="sr-only">{{ $tag['colourName'] }}</span>
    </span>

    <div class="flex flex-none gap-1.5">
      <x-button
        variant="ghost"
        size="small"
        type="button"
        x-ref="edit"
        aria-label="{{ __('Edit :name', ['name' => $tag['name']]) }}"
        @click="editing = true; $nextTick(() => $refs.name.focus())"
      >{{ __('Edit') }}</x-button>

      <x-modal.warning
        :title="__('Delete the tag “:name”?', ['name' => $tag['name']])"
        :confirm="__('Delete tag')"
        :action="$tag['destroyAction']"
        x-target="possession-tags"
      >
        <x-slot:trigger>
          <x-button
            variant="ghost"
            size="small"
            tone="danger"
            type="button"
            aria-label="{{ __('Delete :name', ['name' => $tag['name']]) }}"
            @click="open = true"
          >{{ __('Delete') }}</x-button>
        </x-slot:trigger>

        @if ($tag['deleteConsequence'] !== '')
          <p>{{ $tag['deleteConsequence'] }}</p>
        @endif

        <p>{{ __('This cannot be undone.') }}</p>
      </x-modal.warning>
    </div>
  </div>

  <x-form
    x-show="editing"
    x-cloak
    method="put"
    :action="$tag['updateAction']"
    x-target="possession-tags"
    class="flex flex-col gap-3.5 bg-card p-4"
  >
    <div class="flex flex-wrap items-end gap-4">
      <div class="min-w-55 flex-1">
        <x-input
          :id="'possession-tag-name-'.$tag['uuid']"
          name="name"
          :label="__('Name')"
          :value="$tagErrors->any() ? old('name', $tag['name']) : $tag['name']"
          :error="$tagErrors->get('name')"
          maxlength="50"
          required
          x-ref="name"
        />
      </div>

      @include('app.settings.account.possession-tags._colours', [
        'colours' => $colours,
        'checked' => $tag['colour'],
        'idPrefix' => 'possession-tag-colour-'.$tag['uuid'],
      ])
    </div>

    <div class="flex justify-end gap-2">
      <x-button
        variant="secondary"
        size="small"
        type="button"
        @click="editing = false; $nextTick(() => $refs.edit.focus())"
      >{{ __('Cancel') }}</x-button>

      <x-button size="small" type="submit">{{ __('Save changes') }}</x-button>
    </div>
  </x-form>
</li>
