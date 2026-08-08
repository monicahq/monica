{{--
  The form that declares a kind of record, and the same form that changes one.
  Naming a type and renaming it ask for the same things, so there is one screen
  and the view model decides what it says.

  Everything the preview shows is held in the browser, since it has to follow
  the typing rather than the saving. The slug shown while creating is a guess
  at what the server will settle on; the field is read only either way, because
  the slug is derived on creation and fixed afterwards.

  The row of buttons at the foot sits outside the form rather than inside it,
  since deleting the type is a form of its own and forms do not nest. The save
  button reaches back into the form by id, which is what the `form` attribute
  is for, and the alpine state wraps both so the button can read the fields.

  @var \App\ViewModels\Settings\Account\EntityTypes\EntityTypeFormViewModel $viewModel
--}}
@php
  $values = $viewModel->values();
  $panel = 'grid gap-x-9 gap-y-5 md:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)]';
@endphp
<x-app-layout :title="$viewModel->title()">
  <x-slot:sidebar>
    <x-settings.sidebar :user="$viewModel->user()" current="entities" />
  </x-slot:sidebar>

  <x-slot:breadcrumb>
    <nav class="text-sm text-muted" aria-label="{{ __('Breadcrumb') }}">
      {{ __('Settings') }}
      <span class="px-1 text-placeholder" aria-hidden="true">/</span>
      <a href="{{ route('settings.entityTypes.index') }}" data-turbo="true" class="hover:text-ink">{{ __('Entity types') }}</a>
      <span class="px-1 text-placeholder" aria-hidden="true">/</span>
      <span class="text-ink" aria-current="page">{{ $viewModel->isEdit() ? $values['nameSingular'] : __('New type') }}</span>
    </nav>
  </x-slot:breadcrumb>

  <div class="space-y-1.5">
    <a
      href="{{ route('settings.entityTypes.index') }}"
      data-turbo="true"
      class="-ml-1.5 inline-flex items-center gap-1.5 rounded-md px-1.5 py-1 text-sm text-muted transition-colors duration-150 hover:bg-hover hover:text-ink"
    >
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M9.6 3.6 5.2 8l4.4 4.4"></path>
      </svg>
      {{ __('Entity types') }}
    </a>

    <x-page-header :title="$viewModel->title()" :description="$viewModel->description()" />
  </div>

  <div
    x-data="{
      nameSingular: @js($values['nameSingular']),
      namePlural: @js($values['namePlural']),
      icon: @js($values['icon']),
      colour: @js($values['colour']),
      storedSlug: @js($viewModel->slug()),
      get slug() { return this.storedSlug ?? window.slugify(this.nameSingular) },
      get previewSlug() { return this.slug || '…' },
      get previewName() { return this.namePlural.trim() || this.nameSingular.trim() || @js(__('Your new type')) },
      get named() { return this.nameSingular.trim() !== '' && this.namePlural.trim() !== '' },
      get tint() { return `color-mix(in srgb, ${this.colour} 12%, transparent)` },
    }"
    class="space-y-8.5"
  >
    <x-form id="entity-type-form" :method="$viewModel->method()" :action="$viewModel->action()" class="space-y-8.5">
      <x-box :title="__('Names')">
        <div class="{{ $panel }}">
          <div class="space-y-2.5 text-sm leading-relaxed text-body">
            <p>{{ __('One name for a single record, one for the list. We use the singular in buttons and headers, the plural in list titles.') }}</p>

            <p>{{ __('The slug is what the URL and the API use. It has to be unique in your account.') }}</p>
          </div>

          <div class="space-y-3.5">
            <div class="grid gap-3 sm:grid-cols-2">
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

            <div class="space-y-1.5">
              <x-label for="slug" :value="__('Slug')" />

              <input
                id="slug"
                type="text"
                :value="slug"
                readonly
                disabled
                aria-describedby="slug-help"
                class="block w-full appearance-none rounded-md border border-hairline-strong bg-input px-3 py-2.5 font-mono text-base text-ink opacity-60 sm:text-sm"
              />

              <p id="slug-help" class="text-xs text-muted">{{ $viewModel->slugHelp() }}</p>
            </div>

            <x-textarea
              id="description"
              :label="__('Description')"
              :value="$values['description']"
              :placeholder="__('Cats, dogs, the neighbour’s parrot.')"
              :help="__('Only you see this, on this screen.')"
              :error="$errors->get('description')"
            />
          </div>
        </div>
      </x-box>

      <x-box :title="__('Appearance')">
        <div class="{{ $panel }}">
          <div class="space-y-3.5">
            <p class="text-sm leading-relaxed text-body">{{ __('The icon and colour mark this type in the vault navigation and beside every record of it.') }}</p>

            <div class="flex items-center gap-2.5 rounded-lg border border-hairline-soft bg-sunken px-3 py-2.5">
              <span
                :style="`color: ${colour}; background: ${tint}`"
                class="flex size-7 shrink-0 items-center justify-center rounded-md"
                aria-hidden="true"
              >
                @foreach (\App\Models\EntityType::ICONS as $glyph)
                  <span x-cloak x-show="icon === @js($glyph)" class="flex">
                    <x-entity-icon :name="$glyph" />
                  </span>
                @endforeach
              </span>

              <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-ink" x-text="previewName"></p>

                <p class="truncate font-mono text-xs text-muted-soft" x-text="previewSlug"></p>
              </div>
            </div>
          </div>

          <div class="space-y-4">
            <fieldset class="space-y-1.5">
              <legend class="text-sm font-semibold text-ink">{{ __('Icon') }}</legend>

              <input type="hidden" name="icon" value="{{ $values['icon'] }}" :value="icon" />

              <div class="flex flex-wrap gap-1.5">
                @foreach ($viewModel->icons() as $option)
                  <button
                    type="button"
                    aria-label="{{ $option['name'] }}"
                    :aria-pressed="icon === @js($option['name']) ? 'true' : 'false'"
                    @click="icon = @js($option['name'])"
                    :class="icon === @js($option['name']) ? 'border-primary bg-sunken text-ink' : 'border-hairline-strong bg-canvas text-muted hover:bg-hover'"
                    class="flex size-9 cursor-pointer items-center justify-center rounded-md border transition-colors duration-150"
                  >
                    <x-entity-icon :name="$option['name']" :size="16" />
                  </button>
                @endforeach
              </div>

              <x-error :id="$errors->has('icon') ? 'icon-error' : null" :messages="$errors->get('icon')" />
            </fieldset>

            <fieldset class="space-y-1.5">
              <legend class="text-sm font-semibold text-ink">{{ __('Colour') }}</legend>

              <input type="hidden" name="colour" value="{{ $values['colour'] }}" :value="colour" />

              <div class="flex flex-wrap gap-1.5">
                @foreach ($viewModel->colours() as $option)
                  <button
                    type="button"
                    aria-label="{{ $option['value'] }}"
                    :aria-pressed="colour === @js($option['value']) ? 'true' : 'false'"
                    @click="colour = @js($option['value'])"
                    :class="colour === @js($option['value']) ? 'border-primary' : 'border-hairline-strong'"
                    class="flex size-9 cursor-pointer items-center justify-center rounded-md border bg-canvas transition-colors duration-150"
                  >
                    <span style="background: {{ $option['value'] }}" class="size-[18px] rounded-sm"></span>
                  </button>
                @endforeach
              </div>

              <p class="font-mono text-xs text-muted" x-text="colour"></p>

              <x-error :id="$errors->has('colour') ? 'colour-error' : null" :messages="$errors->get('colour')" />
            </fieldset>
          </div>
        </div>
      </x-box>

      <x-box :title="__('Availability')">
        <div class="{{ $panel }}">
          <p class="text-sm leading-relaxed text-body">{{ __('An active type shows up in every vault in this account. Turn it off and existing records stay where they are, but no new ones can be made.') }}</p>

          <div class="space-y-4">
            <x-checkbox id="is_active" :checked="$values['isActive']">
              {{ __('New records of this type can be created') }}
            </x-checkbox>

            @if ($viewModel->isEdit())
              <div class="flex flex-wrap items-center gap-x-6 gap-y-1 border-t border-hairline-soft pt-3.5 text-xs text-muted-soft">
                <span>{{ $viewModel->recordCount() }}</span>

                <span class="font-mono break-all">{{ $viewModel->uuid() }}</span>
              </div>
            @endif
          </div>
        </div>
      </x-box>
    </x-form>

    <div class="flex flex-wrap items-center gap-3.5">
      @if ($viewModel->isEdit())
        @include('app.settings.account.entity-types._delete', ['viewModel' => $viewModel])
      @endif

      <div class="flex flex-wrap items-center gap-3 max-sm:w-full sm:ml-auto">
        <span x-cloak x-show="! named" class="text-xs text-muted-soft">{{ __('Both names are all we really need.') }}</span>

        <x-button.secondary :href="route('settings.entityTypes.index')" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

        <x-button form="entity-type-form" ::disabled="! named">{{ $viewModel->saveLabel() }}</x-button>
      </div>
    </div>
  </div>
</x-app-layout>
