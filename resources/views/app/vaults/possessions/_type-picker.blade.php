{{-- The picker of the type of a new possession, a searchable list, and the fields that type brings. --}}
{{-- @var \App\ViewModels\Vaults\PossessionFormViewModel $viewModel --}}
@php
  $types = $viewModel->types();
  $recentTypes = $viewModel->recentTypes();
  $fieldsBlock = $viewModel->fieldsBlock();
  $typeError = $errors->get('type');
@endphp

<x-panel :flush="true" :clip="false">
  @if ($types === [])
    <div class="flex flex-col gap-1 p-4">
      <p class="text-sm text-ink">{{ __('Your account has no possession type yet.') }}</p>
      <p class="text-sm text-muted">
        {{ __('Add one in the possession types settings, then come back.') }}
        <a href="{{ $viewModel->typesSettingsUrl() }}" class="focus-ring rounded-sm text-link underline decoration-hairline-strong underline-offset-2 hover:decoration-ink">{{ __('Manage types') }}</a>
      </p>
      <x-error id="type-error" :messages="$typeError" />
    </div>
  @else
    <div
      x-data="possessionTypePicker({
        types: @js($types),
        recent: @js(array_column($recentTypes, 'uuid')),
        chosen: @js($viewModel->chosenType()),
        labels: @js(['recent' => __('Recently used'), 'all' => __('All types'), 'matching' => __('Matching types'), 'noMatch' => __('No type called “:query”.')]),
      })"
      class="flex flex-col gap-2.5 p-4"
    >
      <input type="hidden" name="type" :value="chosen" value="{{ $viewModel->chosenType() }}" />

      <p id="possession-type-label" class="text-label text-ink">{{ __('Type') }}</p>

      <div class="relative" @click.outside="close(false)" @keydown.escape.stop.prevent="close()">
        <button
          id="possession-type-trigger"
          type="button"
          x-ref="trigger"
          aria-haspopup="listbox"
          aria-expanded="false"
          :aria-expanded="open ? 'true' : 'false'"
          aria-labelledby="possession-type-label possession-type-trigger"
          @if ($typeError) aria-invalid="true" aria-describedby="type-error" @endif
          @click="toggle()"
          class="focus-ring flex h-12 w-full items-center gap-2.5 rounded-md border border-hairline-strong bg-input pr-3 pl-2 text-left hover:border-muted-soft aria-[invalid=true]:border-error"
        >
          <template x-if="chosenType">
            <span class="flex min-w-0 flex-1 items-center gap-2.5">
              <span class="flex size-8 flex-none items-center justify-center rounded-md bg-hover" aria-hidden="true">
                <span class="size-3 rounded-full" :style="`background: ${chosenType.colour}`"></span>
              </span>
              <span class="flex min-w-0 flex-1 flex-col">
                <span class="truncate text-sm font-semibold text-ink" x-text="chosenType.name"></span>
                <span class="text-xs text-muted" x-text="chosenType.fieldCount"></span>
              </span>
            </span>
          </template>

          <template x-if="! chosenType">
            <span class="flex min-w-0 flex-1 items-center gap-2.5">
              <span class="flex size-8 flex-none items-center justify-center rounded-md border border-dashed border-hairline-strong text-muted" aria-hidden="true">
                <x-icon name="layers" :size="16" />
              </span>
              <span class="flex-1 text-sm text-muted">{{ __('Choose a type') }}</span>
            </span>
          </template>

          <x-icon name="chevron-down" :size="16" :stroke-width="1.5" class="text-muted" />
        </button>

        <div
          x-show="open"
          x-cloak
          class="absolute top-[calc(100%+6px)] right-0 left-0 z-20 flex flex-col rounded-lg border border-hairline bg-canvas shadow-pop"
        >
          <div class="flex h-11 items-center gap-2 border-b border-hairline px-3">
            <x-icon name="search" :size="15" class="text-muted" />

            <input
              type="text"
              x-ref="search"
              x-model="query"
              @input="search()"
              @keydown.arrow-down.prevent="move(1)"
              @keydown.arrow-up.prevent="move(-1)"
              @keydown.home.prevent="moveTo('first')"
              @keydown.end.prevent="moveTo('last')"
              @keydown.enter.prevent="pickActive()"
              role="combobox"
              aria-autocomplete="list"
              aria-expanded="true"
              aria-controls="possession-type-listbox"
              :aria-activedescendant="activeId"
              aria-label="{{ __('Search the types') }}"
              placeholder="{{ __('Search the types') }}"
              autocomplete="off"
              class="h-full min-w-0 flex-1 border-0 bg-transparent text-[16px] text-ink outline-none sm:text-sm"
            />

            <span class="hidden text-xs text-muted sm:inline">{{ __('Esc to close') }}</span>
          </div>

          <ul id="possession-type-listbox" role="listbox" aria-labelledby="possession-type-label" class="max-h-75 overflow-y-auto p-1.5">
            <template x-for="group in groups" :key="group.key">
              <li role="presentation" class="flex flex-col gap-px pb-1.5">
                <x-caption class="px-2 pt-2 pb-1" aria-hidden="true"><span x-text="labels[group.key]"></span></x-caption>

                <ul role="group" :aria-label="labels[group.key]">
                  <template x-for="type in group.items" :key="group.key + type.uuid">
                    <li
                      role="option"
                      :id="optionId(group.key, type.uuid)"
                      :aria-selected="active === optionId(group.key, type.uuid) ? 'true' : 'false'"
                      @click="pick(type.uuid)"
                      @mousemove="active = optionId(group.key, type.uuid)"
                      class="flex w-full cursor-pointer items-center gap-2.5 rounded-md px-2 py-1.5 text-left aria-selected:bg-hover"
                    >
                      <span class="size-2.5 flex-none rounded-full" :style="`background: ${type.colour}`" aria-hidden="true"></span>
                      <span class="min-w-0 flex-1 truncate text-sm text-ink" x-text="type.name"></span>
                      <span class="sr-only" x-text="type.colourName"></span>
                      <span class="text-xs text-muted" x-text="type.fieldCount"></span>
                      <span class="flex w-4 text-ink" aria-hidden="true">
                        <span x-show="chosen === type.uuid"><x-icon name="check" :size="16" /></span>
                      </span>
                    </li>
                  </template>
                </ul>
              </li>
            </template>
          </ul>

          <div x-show="groups.length === 0" class="flex flex-col gap-1 px-3 pb-4" aria-live="polite">
            <p class="text-sm text-ink" x-text="noMatchLine"></p>
            <p class="text-sm text-muted">{{ __('You can create it in the possession types settings, then come back.') }}</p>
          </div>

          <div class="flex items-center gap-2 border-t border-hairline px-3 py-2.5 text-sm">
            <span class="flex-1 text-muted">{{ $viewModel->typeCountLine() }}</span>
            <a href="{{ $viewModel->typesSettingsUrl() }}" class="focus-ring rounded-sm text-link underline decoration-hairline-strong underline-offset-2 hover:decoration-ink">{{ __('Manage types') }}</a>
          </div>
        </div>
      </div>

      <x-error id="type-error" :messages="$typeError" />

      @if ($recentTypes !== [])
        <div class="flex flex-wrap items-center gap-1.5">
          <span id="possession-type-recent" class="mr-0.5 text-xs text-muted">{{ __('Recently used') }}</span>

          @foreach ($recentTypes as $recentType)
            <button
              type="button"
              @click="pick(@js($recentType['uuid']))"
              :aria-pressed="chosen === @js($recentType['uuid']) ? 'true' : 'false'"
              aria-pressed="false"
              aria-describedby="possession-type-recent"
              class="focus-ring inline-flex h-6.5 items-center gap-1.5 rounded-full border border-hairline bg-canvas px-2.5 text-xs text-ink hover:bg-hover aria-pressed:border-hairline-strong aria-pressed:bg-hover"
            >
              <span class="size-1.75 rounded-full" style="background: {{ $recentType['colour'] }}" aria-hidden="true"></span>
              {{ $recentType['name'] }}
            </button>
          @endforeach
        </div>
      @endif
    </div>
  @endif

  <div class="rounded-b-lg border-t border-hairline bg-card">
    @include('app.vaults.possessions._type-fields', ['fieldsBlock' => $fieldsBlock])
  </div>
</x-panel>
