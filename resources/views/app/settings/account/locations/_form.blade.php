{{-- The form that adds a location or changes one, opened above the tree. --}}
{{-- @var \App\ViewModels\Settings\Account\Locations\LocationsViewModel $viewModel --}}
@php
  $values = $viewModel->values();
  $suggestions = $viewModel->emojiSuggestions();
@endphp
<div
  x-data="{
    emoji: @js($values['emoji']),
    parent: @js($values['parent']),
    helps: @js($viewModel->addressHelpByParent()),
    defaultHelp: @js($viewModel->defaultAddressHelp()),
    other: @js(in_array($values['emoji'], $suggestions, true) ? '' : $values['emoji']),
    get addressHelp() { return this.helps[this.parent] ?? this.defaultHelp },
  }"
  x-effect="document.getElementById('location-address-help').textContent = addressHelp"
>
  <x-form :method="$viewModel->formMethod()" :action="$viewModel->formAction()">
    <x-panel :title="$viewModel->formTitle()" icon="place">
      <div class="flex flex-col gap-4.5">
        <div class="grid gap-4.5 sm:grid-cols-2">
          <x-input
            id="location-name"
            name="name"
            :value="$values['name']"
            :label="__('Name')"
            :placeholder="__('Living room, top shelf, box 3')"
            :error="$errors->get('name')"
            maxlength="255"
            required
            autofocus
          />

          <x-select
            id="location-parent"
            name="parent"
            :label="__('Sits inside')"
            optional
            x-model="parent"
            :error="$errors->get('parent')"
          >
            <option value="">{{ __('Nothing, at the top level') }}</option>
            @foreach ($viewModel->parentOptions() as $option)
              <option value="{{ $option['value'] }}" @selected($values['parent'] === $option['value'])>{{ $option['label'] }}</option>
            @endforeach
          </x-select>
        </div>

        <div class="flex flex-col gap-2">
          <span id="location-emoji-label" class="text-label text-ink">{{ __('Emoji') }}</span>

          <input type="hidden" name="emoji" value="{{ $values['emoji'] }}" :value="emoji" />

          <div class="flex flex-wrap gap-1.5" role="group" aria-labelledby="location-emoji-label">
            <button
              type="button"
              aria-label="{{ __('No emoji') }}"
              :aria-pressed="emoji === '' ? 'true' : 'false'"
              @click="emoji = ''; other = ''"
              :class="emoji === '' ? 'border-ink bg-hover' : 'border-hairline bg-canvas'"
              class="flex size-9 cursor-pointer items-center justify-center rounded-md border text-muted hover:bg-hover focus-ring"
            >–</button>

            @foreach ($suggestions as $suggestion)
              <button
                type="button"
                aria-label="{{ $suggestion }}"
                :aria-pressed="emoji === @js($suggestion) ? 'true' : 'false'"
                @click="emoji = @js($suggestion); other = ''"
                :class="emoji === @js($suggestion) ? 'border-ink bg-hover' : 'border-hairline bg-canvas'"
                class="flex size-9 cursor-pointer items-center justify-center rounded-md border text-[17px] leading-none hover:bg-hover focus-ring"
              >{{ $suggestion }}</button>
            @endforeach

            <input
              id="location-emoji-other"
              type="text"
              x-model="other"
              @input="emoji = $event.target.value.trim()"
              placeholder="{{ __('Other') }}"
              aria-label="{{ __('Another emoji') }}"
              @if ($errors->has('emoji')) aria-invalid="true" aria-describedby="location-emoji-error" @endif
              maxlength="32"
              class="h-9 w-20 rounded-md border border-hairline-strong bg-input px-2.5 text-[16px] text-ink focus-ring-field aria-[invalid=true]:border-error"
            />
          </div>

          <p class="text-xs text-muted">{{ __('Without an emoji, the location keeps the default icon.') }}</p>

          <x-error id="location-emoji-error" :messages="$errors->get('emoji')" />
        </div>

        <x-textarea
          id="location-address"
          name="address"
          :label="__('Address')"
          optional
          :value="$values['address']"
          :placeholder="__('1725 Slough Avenue')"
          :help="$viewModel->defaultAddressHelp()"
          :error="$errors->get('address')"
        />
      </div>

      <x-slot:footer>
        <div class="flex flex-wrap justify-end gap-3">
          <x-button :href="$viewModel->indexUrl()" variant="secondary" size="small">{{ __('Cancel') }}</x-button>

          <x-button size="small">{{ $viewModel->saveLabel() }}</x-button>
        </div>
      </x-slot:footer>
    </x-panel>
  </x-form>
</div>
