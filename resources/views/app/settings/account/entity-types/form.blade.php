{{-- The form that declares a kind of record, and the same form that changes one. --}}
{{-- @var \App\ViewModels\Settings\Account\EntityTypes\EntityTypeFormViewModel $viewModel --}}
@php
  $values = $viewModel->values();

  $swatch = 'flex size-9 cursor-pointer items-center justify-center rounded-sm border transition-colors duration-150';
  $swatchOn = "'border-ink bg-ink text-canvas'";
  $swatchOff = "'border-hairline-strong bg-canvas text-muted hover:bg-hover'";
@endphp
<x-settings.page
  :title="$viewModel->title()"
  :user="$viewModel->user()"
  current="entity-types"
  :back-href="route('settings.entityTypes.index')"
  :back-label="__('Back to the types')"
>
  <x-page-header :title="$viewModel->title()" :description="$viewModel->description()" />

  <div
    x-data="{
      nameSingular: @js($values['nameSingular']),
      namePlural: @js($values['namePlural']),
      icon: @js($values['icon']),
      colour: @js($values['colour']),
      slug: @js($values['slug']),
      slugTouched: @js($values['slug'] !== ''),
      get previewSlug() { return this.slug || '…' },
      get previewName() { return this.namePlural.trim() || this.nameSingular.trim() || @js(__('Your new type')) },
      get named() { return this.nameSingular.trim() !== '' && this.namePlural.trim() !== '' },
      get tint() { return `color-mix(in srgb, ${this.colour} 12%, transparent)` },
    }"
    @if ($viewModel->slugIsEditable())
      x-effect="if (! slugTouched) slug = window.slugify(nameSingular)"
    @endif
    class="flex flex-col gap-8"
  >
    <x-form id="entity-type-form" :method="$viewModel->method()" :action="$viewModel->action()" class="flex flex-col gap-8">
      <x-page-section :title="__('Names')" :description="__('One name for a single record, one for the list. The singular goes in buttons and headers, the plural in list titles.')">
        <x-panel>
          <div class="flex flex-col gap-4.5">
            <div class="grid gap-4 sm:grid-cols-2">
              <x-input
                id="name_singular"
                x-model="nameSingular"
                :value="$values['nameSingular']"
                :label="__('Name, singular')"
                :placeholder="__('Animal')"
                :error="$errors->get('name_singular')"
                maxlength="255"
                required
              />

              <x-input
                id="name_plural"
                x-model="namePlural"
                :value="$values['namePlural']"
                :label="__('Name, plural')"
                :placeholder="__('Animals')"
                :error="$errors->get('name_plural')"
                maxlength="255"
                required
              />
            </div>

            @if ($viewModel->slugIsEditable())
              <x-input
                id="slug"
                x-model="slug"
                @input="slugTouched = $event.target.value.trim() !== ''"
                @blur="slug = window.slugify(slug)"
                :value="$values['slug']"
                :label="__('Slug')"
                :placeholder="__('animal')"
                :help="$viewModel->slugHelp()"
                :error="$errors->get('slug')"
                :maxlength="\App\Models\EntityType::SLUG_LENGTH"
                class="font-mono"
              />
            @else
              <div class="flex flex-col gap-1.5">
                <p class="flex items-baseline gap-1.5 text-label text-ink">{{ __('Slug') }}</p>

                <x-code-value>{{ $values['slug'] }}</x-code-value>

                <p class="text-xs text-muted">{{ $viewModel->slugHelp() }}</p>
              </div>
            @endif

            <x-textarea
              id="description"
              :label="__('Description')"
              optional
              :value="$values['description']"
              :placeholder="__('Cats, dogs, the neighbour’s parrot.')"
              :help="__('Only you see this, on this screen.')"
              :error="$errors->get('description')"
            />
          </div>
        </x-panel>
      </x-page-section>

      <x-page-section :title="__('Appearance')" :description="__('The icon and colour mark this type in the vault navigation and beside every record of it.')">
        <x-panel>
          <div class="flex flex-col gap-5">
            <div class="flex w-full items-center gap-3 rounded-md border border-hairline bg-sunken px-3 py-2.5 text-left">
              <span
                class="flex size-8 shrink-0 items-center justify-center rounded-sm"
                :style="`color: ${colour}; background: ${tint}`"
                aria-hidden="true"
              >
                @foreach (\App\Models\EntityType::ICONS as $glyph)
                  <span x-cloak x-show="icon === @js($glyph)" class="flex">
                    <x-entity-icon :name="$glyph" :size="18" />
                  </span>
                @endforeach
              </span>

              <div class="min-w-0 flex-1">
                <p class="truncate text-base font-semibold text-ink" x-text="previewName"></p>

                <p class="truncate font-mono text-xs text-muted" x-text="previewSlug"></p>
              </div>
            </div>

            <fieldset class="flex flex-col gap-1.5">
              <legend class="flex items-baseline gap-1.5 text-label text-ink">{{ __('Icon') }}</legend>

              <input type="hidden" name="icon" value="{{ $values['icon'] }}" :value="icon" />

              <div class="flex flex-wrap gap-1.5">
                @foreach ($viewModel->icons() as $option)
                  <button
                    type="button"
                    aria-label="{{ $option['name'] }}"
                    :aria-pressed="icon === @js($option['name']) ? 'true' : 'false'"
                    @click="icon = @js($option['name'])"
                    :class="icon === @js($option['name']) ? {{ $swatchOn }} : {{ $swatchOff }}"
                    class="{{ $swatch }}"
                  >
                    <x-entity-icon :name="$option['name']" :size="16" />
                  </button>
                @endforeach
              </div>

              <x-error :id="$errors->has('icon') ? 'icon-error' : null" :messages="$errors->get('icon')" />
            </fieldset>

            <fieldset class="flex flex-col gap-1.5">
              <legend class="flex items-baseline gap-1.5 text-label text-ink">{{ __('Colour') }}</legend>

              <input type="hidden" name="colour" value="{{ $values['colour'] }}" :value="colour" />

              <div class="flex flex-wrap gap-1.5">
                @foreach ($viewModel->colours() as $option)
                  <button
                    type="button"
                    aria-label="{{ $option['value'] }}"
                    :aria-pressed="colour === @js($option['value']) ? 'true' : 'false'"
                    @click="colour = @js($option['value'])"
                    :class="colour === @js($option['value']) ? 'border-ink' : 'border-hairline-strong'"
                    class="{{ $swatch }} bg-canvas"
                  >
                    <span style="background: {{ $option['value'] }}" class="size-4.5 rounded-xs"></span>
                  </button>
                @endforeach
              </div>

              <p class="font-mono text-xs text-muted" x-text="colour"></p>

              <x-error :id="$errors->has('colour') ? 'colour-error' : null" :messages="$errors->get('colour')" />
            </fieldset>
          </div>
        </x-panel>
      </x-page-section>

      <x-page-section :title="__('Availability')" :description="__('An active type appears in every vault in this account. Turn it off and existing records stay as they are, but no new ones can be made.')">
        <x-panel>
          <x-checkbox id="is_active" :checked="$values['isActive']">
            {{ __('New records of this type can be created') }}
          </x-checkbox>

          @if ($viewModel->isEdit())
            <x-slot:footer>
              <div class="flex flex-wrap items-center gap-x-6 gap-y-1">
                <span class="text-xs text-pretty text-muted">{{ $viewModel->recordCount() }}</span>

                <span class="font-mono text-xs break-all text-muted">{{ $viewModel->uuid() }}</span>
              </div>
            </x-slot:footer>
          @endif
        </x-panel>
      </x-page-section>
    </x-form>

    <div class="flex flex-wrap items-center gap-3.5">
      @if ($viewModel->isEdit())
        @include('app.settings.account.entity-types._delete', ['viewModel' => $viewModel])
      @endif

      <div class="flex flex-wrap items-center gap-3 max-sm:w-full sm:ml-auto">
        <span x-cloak x-show="! named" class="text-xs text-pretty text-muted">{{ __('Both names are enough.') }}</span>

        <x-button.secondary :href="route('settings.entityTypes.index')" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

        <x-button form="entity-type-form" ::disabled="! named">{{ $viewModel->saveLabel() }}</x-button>
      </div>
    </div>
  </div>
</x-settings.page>
