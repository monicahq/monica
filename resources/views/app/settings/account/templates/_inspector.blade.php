{{--
  The panel that edits whatever field is open.

  It sticks to the top of the window on a wide screen, so picking a field far down
  a long section does not leave the panel about it off the screen.

  The type is shown and not offered. It is the one decision about a field that
  cannot be taken back: it decides what a stored value means, and changing it
  under values already written would quietly turn them into something else. The
  way to change a type is to remove the field and add the one that was wanted,
  which is what the line under it says. It is a value rather than a disabled box,
  for the same reason a settled slug is.

  @var \App\ViewModels\Settings\Account\Templates\TemplateBuilderViewModel $viewModel
  @var \App\Models\Template $template
--}}
@php
  $field = $viewModel->selectedField();
@endphp
<aside class="lg:sticky lg:top-0">
  @if ($field === null)
    <x-panel :title="__('Field')" icon="file">
      <p class="text-sm text-pretty text-muted">{{ __('Pick a field on the left to edit it. What you change applies to this draft only.') }}</p>
    </x-panel>
  @else
    <x-panel
      :title="__('Field')"
      :subtitle="__('In :section', ['section' => $field['sectionName']])"
      icon="file"
    >
      <div class="flex flex-col gap-5">
        <x-form
          id="field-form"
          method="put"
          :action="route('settings.fields.update', [$template, $field['rowId'], $field['id']])"
          x-target="builder"
          class="flex flex-col gap-4"
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

          <div class="flex flex-col gap-1.5">
            <p class="flex items-baseline gap-1.5 text-label text-ink">{{ __('Type') }}</p>

            <p class="rounded-md border border-hairline bg-sunken px-3 py-2 text-sm text-ink">{{ $field['typeLabel'] }}</p>

            <p class="text-xs text-muted">{{ __('Settled when the field was added. To change it, remove the field and add the one you wanted.') }}</p>
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

          <div class="flex flex-col gap-2.5 border-t border-hairline pt-4">
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
            <x-button size="small" class="self-start">{{ __('Save the field') }}</x-button>
          @endif
        </x-form>

        @if ($viewModel->isEditable())
          <div class="flex flex-col items-start gap-2.5 border-t border-hairline pt-4">
            <p class="uppercase text-xs text-pretty tracking-caption text-muted">{{ __('In use') }}</p>

            <p class="text-sm text-pretty text-muted">{{ $viewModel->usageNote() }}</p>

            <x-modal.warning
              :title="__('Delete “:label”?', ['label' => $field['label']])"
              :confirm="__('Delete the field')"
              :action="route('settings.fields.destroy', [$template, $field['rowId'], $field['id']])"
              x-target="builder"
            >
              <x-slot:trigger>
                <x-button.secondary size="small" type="button" @click="open = true" class="text-error">
                  {{ __('Delete the field') }}
                </x-button.secondary>
              </x-slot:trigger>

              <p>{{ __('It goes off this draft. Anything already published keeps it.') }}</p>
            </x-modal.warning>
          </div>
        @endif
      </div>
    </x-panel>
  @endif
</aside>
