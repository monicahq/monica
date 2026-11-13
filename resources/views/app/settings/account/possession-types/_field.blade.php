{{-- One field of a possession type: its name, its kind, its choices, and its place among its siblings. --}}
{{--
  @var array $field
  @var list<array{value: string, label: string}> $kinds
--}}
@php
  $fieldErrors = $errors->getBag('field'.$field['uuid']);
  $moved = session('moved') === 'field-'.$field['uuid'];
@endphp
<div id="field-{{ $field['uuid'] }}" key="field-{{ $field['uuid'] }}" class="rounded-md border border-hairline bg-canvas p-3">
  <x-form method="put" :action="$field['updateAction']" x-target="field-{{ $field['uuid'] }} possession-type-saved" class="flex flex-col gap-3">
    <div class="flex flex-wrap items-end gap-2.5">
      <div class="min-w-48 flex-1">
        <x-input
          :id="'field-'.$field['uuid'].'-name'"
          name="name"
          size="small"
          :value="$field['name']"
          :label="__('Field name')"
          :error="$fieldErrors->get('name')"
          maxlength="255"
          required
          @change="$el.form.requestSubmit()"
        />
      </div>

      <x-select
        :id="'field-'.$field['uuid'].'-kind'"
        name="kind"
        size="small"
        :label="__('Kind')"
        class="w-52"
        @change="$el.form.requestSubmit()"
      >
        @foreach ($kinds as $kind)
          <option value="{{ $kind['value'] }}" @selected($kind['value'] === $field['kind'])>{{ $kind['label'] }}</option>
        @endforeach
      </x-select>
    </div>

    @if ($field['isSelect'])
      <fieldset class="flex flex-col gap-2 border-t border-hairline-soft pt-3">
        <legend class="mb-2 text-label text-ink">{{ __('Choices') }}</legend>

        @foreach ($field['options'] as $option)
          <div class="flex items-center gap-2" data-choice>
            <input type="hidden" name="options[]" value="{{ $option }}" />
            <span class="flex-1 rounded-md bg-card px-3 py-1.5 text-sm text-ink">{{ $option }}</span>
            <x-modal.warning
              method="put"
              :title="__('Remove the choice “:name”?', ['name' => $option])"
              :confirm="__('Remove the choice')"
              :cancel="__('Keep it')"
              :action="$field['updateAction']"
              x-target="field-{{ $field['uuid'] }} possession-type-saved"
            >
              <x-slot:trigger>
                <x-icon-button icon="close" :label="__('Remove the choice :name', ['name' => $option])" align="right" @click="open = true" />
              </x-slot:trigger>

              <x-slot:fields>
                <input type="hidden" name="name" value="{{ $field['name'] }}" />
                <input type="hidden" name="kind" value="{{ $field['kind'] }}" />
                @foreach ($field['options'] as $kept)
                  @if ($kept !== $option)
                    <input type="hidden" name="options[]" value="{{ $kept }}" />
                  @endif
                @endforeach
              </x-slot:fields>

              <p>{{ $field['choiceWarnings'][$option] }}</p>
            </x-modal.warning>
          </div>
        @endforeach

        @if ($field['needsOptions'])
          <p class="text-sm text-muted">{{ __('A choice field needs at least one choice.') }}</p>
        @endif

        <div class="flex gap-2">
          <div class="flex-1">
            <x-input
              :id="'field-'.$field['uuid'].'-new-option'"
              name="options[]"
              size="small"
              :aria-label="__('New choice')"
              :placeholder="__('Add a choice')"
              :error="$fieldErrors->get('options')"
              maxlength="255"
            />
          </div>
          <x-button.secondary type="submit" size="small">{{ __('Add') }}</x-button.secondary>
        </div>
      </fieldset>
    @endif
  </x-form>

  <div class="mt-2 flex items-center justify-end gap-1">
    @unless ($field['isFirst'])
      <x-form method="put" :action="$field['positionAction']" x-target="possession-type-fields possession-type-saved">
        <input type="hidden" name="position" value="{{ $field['position'] - 1 }}" />
        <x-icon-button type="submit" icon="chevron-up" :label="__('Move :name up', ['name' => $field['name']])" :x-autofocus="$moved && (session('direction') === 'up' || $field['isLast'])" />
      </x-form>
    @endunless

    @unless ($field['isLast'])
      <x-form method="put" :action="$field['positionAction']" x-target="possession-type-fields possession-type-saved">
        <input type="hidden" name="position" value="{{ $field['position'] + 1 }}" />
        <x-icon-button type="submit" icon="chevron-down" :label="__('Move :name down', ['name' => $field['name']])" :x-autofocus="$moved && (session('direction') === 'down' || $field['isFirst'])" />
      </x-form>
    @endunless

    <x-modal.warning
      :title="__('Remove the field “:name”?', ['name' => $field['name']])"
      :confirm="__('Remove the field')"
      :cancel="__('Keep it')"
      :action="$field['destroyAction']"
      x-target="possession-type-fields possession-type-saved"
    >
      <x-slot:trigger>
        <x-icon-button icon="close" :label="__('Remove :name', ['name' => $field['name']])" align="right" @click="open = true" />
      </x-slot:trigger>

      <p>{{ __('This cannot be undone.') }}</p>
    </x-modal.warning>
  </div>
</div>
