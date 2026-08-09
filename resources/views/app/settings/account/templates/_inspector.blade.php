{{--
  The panel that edits whatever field is open.

  The type is shown and not offered. It is the one decision about a field that
  cannot be taken back: it decides what a stored value means, and changing it
  under values already written would quietly turn them into something else. The
  way to change a type is to remove the field and add the one that was wanted,
  which is what the line under it says.

  @var \App\ViewModels\Settings\Account\Templates\TemplateBuilderViewModel $viewModel
  @var \App\Models\Template $template
--}}
@php
  $field = $viewModel->selectedField();
@endphp
<aside class="lg:sticky lg:top-18">
  @if ($field === null)
    <x-box :title="__('Field')">
      <div class="space-y-2">
        <span class="flex text-placeholder" aria-hidden="true">
          <x-entity-icon name="info" :size="20" />
        </span>

        <p class="text-sm leading-relaxed text-muted">{{ __('Pick a field on the left to edit it. What you change applies to this draft only.') }}</p>
      </div>
    </x-box>
  @else
    <x-box :title="__('Field')">
      <x-slot:description>
        <p>{{ __('In :section', ['section' => $field['sectionName']]) }}</p>
      </x-slot:description>

      <div class="space-y-5">
        <x-form
          id="field-form"
          method="put"
          :action="route('settings.fields.update', [$template, $field['rowId'], $field['id']])"
          x-target="builder"
          class="space-y-4"
        >
          <x-input
            id="label"
            name="label"
            :value="$field['label']"
            :label="__('Label')"
            :error="$errors->get('label')"
            maxlength="255"
            required
            :disabled="! $viewModel->isEditable()"
          />

          <div class="space-y-1.5">
            <x-label for="type" :value="__('Type')" />

            <input
              id="type"
              type="text"
              value="{{ $field['typeLabel'] }}"
              readonly
              disabled
              aria-describedby="type-help"
              class="block w-full appearance-none rounded-md border border-hairline-strong bg-input px-3 py-2.5 text-base text-ink opacity-60 sm:text-sm"
            />

            <p id="type-help" class="text-xs text-muted">{{ __('Settled when the field was added. To change it, remove the field and add the one you wanted.') }}</p>
          </div>

          <x-input
            id="help_text"
            name="help_text"
            :value="$field['help']"
            :label="__('Help text')"
            optional
            :placeholder="__('Shown under the input')"
            :error="$errors->get('help_text')"
            maxlength="255"
            :disabled="! $viewModel->isEditable()"
          />

          <x-input
            id="placeholder"
            name="placeholder"
            :value="$field['placeholder']"
            :label="__('Placeholder')"
            optional
            :placeholder="__('Shown inside an empty input')"
            :error="$errors->get('placeholder')"
            maxlength="255"
            :disabled="! $viewModel->isEditable()"
          />

          <div class="space-y-2.5 border-t border-hairline-soft pt-4">
            <x-checkbox id="is_required" :checked="$field['isRequired']">
              <span class="font-semibold text-ink">{{ __('Required') }}</span>

              <span class="block text-muted">{{ __('The record cannot be saved without it.') }}</span>
            </x-checkbox>

            <x-checkbox id="is_sensitive" :checked="$field['isSensitive']">
              <span class="font-semibold text-ink">{{ __('Sensitive') }}</span>

              <span class="block text-muted">{{ __('Masked until revealed, and left out of a share link. It is protection from a glance over the shoulder, not a permission.') }}</span>
            </x-checkbox>

            <x-checkbox id="is_highlighted" :checked="$field['isHighlighted']">
              <span class="font-semibold text-ink">{{ __('Highlighted') }}</span>

              <span class="block text-muted">{{ __('Shown in the header area of the record as well as in its section.') }}</span>
            </x-checkbox>
          </div>

          @if ($viewModel->isEditable())
            <x-button>{{ __('Save the field') }}</x-button>
          @endif
        </x-form>

        @if ($viewModel->isEditable())
          <div class="space-y-2.5 border-t border-hairline pt-4">
            <p class="text-xs uppercase text-muted-soft">{{ __('In use') }}</p>

            <p class="text-sm leading-relaxed text-body">{{ $viewModel->usageNote() }}</p>

            <x-modal.warning
              :title="__('Delete “:label”?', ['label' => $field['label']])"
              :confirm="__('Delete the field')"
              :action="route('settings.fields.destroy', [$template, $field['rowId'], $field['id']])"
              x-target="builder"
            >
              <x-slot:trigger>
                <button
                  type="button"
                  @click="open = true"
                  class="cursor-pointer rounded-md border border-hairline-strong bg-canvas px-3.5 py-2.5 text-sm font-semibold text-error transition-colors duration-150 hover:bg-hover"
                >{{ __('Delete the field') }}</button>
              </x-slot:trigger>

              <p>{{ __('It goes off this draft. Anything already published keeps it.') }}</p>
            </x-modal.warning>
          </div>
        @endif
      </div>
    </x-box>
  @endif
</aside>
