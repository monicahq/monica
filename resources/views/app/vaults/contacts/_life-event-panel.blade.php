{{-- The panel that records a life event from a contact’s page: choose what happened, then fill it in. --}}
{{-- @var \App\ViewModels\Vaults\LifeEventFormBlock $form --}}
@php
  $oldType = $form->oldType();
  $chip = 'focus-ring inline-flex h-8 cursor-pointer items-center gap-1.5 rounded-full border border-hairline bg-canvas px-3 text-sm font-medium text-body hover:bg-hover';
  $row = 'focus-ring-inset flex w-full items-center gap-3 border-b border-hairline px-5 py-2.5 text-start last:border-b-0 hover:bg-hover';
@endphp

<div id="life-event-panel" x-show="open" x-cloak class="rounded-lg border border-hairline bg-canvas shadow-panel">
  <x-form :method="$form->method()" :action="$form->action()" upload x-target="contact-timeline" x-on:keydown.escape="cancel($el)">
    <input type="hidden" name="life_event_type_id" x-bind:value="type ? type.id : ''" />

    @if ($form->choosesSubject())
      <input type="hidden" name="contact_id" x-bind:value="subject.id" />
    @endif

    <div class="flex flex-wrap items-center gap-3 border-b border-hairline p-5">
      <div class="flex min-w-0 flex-col">
        <h3 class="text-card-title text-ink">{{ $form->title() }}</h3>
        <p class="text-xs text-muted" x-show="step === 'who'" x-cloak>{{ $form->captions()['who'] }}</p>
        <p class="text-xs text-muted" x-show="step === 'pick'">{{ $form->captions()['pick'] }}</p>
        <p class="text-xs text-muted" x-show="step === 'details'" x-cloak>{{ $form->captions()['details'] }}</p>
      </div>

      <div class="ms-auto w-60 max-w-full" x-show="step === 'pick'">
        <x-input
          type="search"
          id="life-event-type-search"
          size="small"
          :placeholder="__('Search every type')"
          aria-label="{{ __('Search every life event type') }}"
          x-model="query"
          x-on:keydown.enter.prevent
          autocomplete="off"
        />
      </div>

      <button type="button" x-on:click="cancel($el)" class="focus-ring ms-auto flex size-8 items-center justify-center rounded-md text-muted hover:bg-hover hover:text-ink" aria-label="{{ __('Close') }}">
        <x-icon name="close" :size="16" />
      </button>
    </div>

    @if ($form->choosesSubject())
      <div x-show="step === 'who'" x-cloak>
        <div class="flex flex-col gap-3 p-5">
          <div class="max-w-90">
            <x-input
              type="search"
              id="life-event-subject-search"
              size="small"
              :placeholder="__('Search the people in this vault')"
              aria-label="{{ __('Search the people in this vault') }}"
              x-model="subjectQuery"
              x-on:input.debounce.400ms="searchSubjects()"
              x-on:keydown.enter.prevent="searchSubjects()"
              autocomplete="off"
            />
          </div>

          <div id="life-event-subjects" x-merge="replace" aria-live="polite" class="dim-when-busy"></div>
        </div>

        <p class="border-t border-hairline p-5 text-sm text-body">
          {{ __('Not in this vault yet?') }}
          <x-link :href="route('contacts.new', $form->vault())" :turbo="true">{{ __('Add somebody first.') }}</x-link>
        </p>
      </div>
    @endif

    <div x-show="step === 'pick'">
      @if ($form->choosesSubject())
        <p class="flex flex-wrap items-center gap-x-2 gap-y-1 border-b border-hairline px-5 py-2.5 text-sm text-body">
          <span>{!! __('For :name', ['name' => '<strong class="font-medium text-ink" x-text="subject.name">'.e($form->contactName()).'</strong>']) !!}</span>
          <button type="button" x-on:click="changeSubject()" class="focus-ring rounded-sm font-medium text-link underline decoration-hairline-strong underline-offset-2 hover:decoration-ink">{{ __('Change') }}</button>
        </p>
      @endif

      <div x-show="changing" x-cloak class="flex items-start gap-2.5 border-b border-hairline p-5 text-sm text-body">
        <span class="pt-0.5 text-muted" aria-hidden="true"><x-icon name="info" :size="15" /></span>
        <p><span x-text="copy.keep"></span> <span x-text="dropNote"></span></p>
      </div>

      @if ($form->typeCount() === 0)
        <p class="p-5 text-sm text-pretty text-muted">
          {{ __('There is no life event type to choose from yet.') }}
          <x-link :href="$form->settingsUrl()">{{ __('Add one in the settings.') }}</x-link>
        </p>
      @else
        <div x-show="! searching" class="flex flex-wrap items-stretch">
          <div class="flex flex-[1_1_11rem] flex-col gap-0.5 border-e border-hairline bg-card px-3 py-5">
            <x-caption class="px-2 pb-2">{{ __('Categories') }}</x-caption>

            @foreach ($form->categories() as $category)
              <button
                type="button"
                x-on:click="category = {{ $category['id'] }}"
                x-bind:aria-current="category === {{ $category['id'] }} ? 'true' : null"
                class="focus-ring flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-start text-sm text-body hover:bg-hover aria-current:bg-hover aria-current:font-medium aria-current:text-ink"
              >
                <span class="min-w-0 flex-1">{{ $category['name'] }}</span>
                <span class="text-xs text-muted tabular-nums">{{ $category['typeCount'] }}</span>
              </button>
            @endforeach
          </div>

          <div class="flex min-w-0 flex-[2_1_17rem] flex-col">
            @foreach ($form->categories() as $category)
              <div x-show="category === {{ $category['id'] }}" @if (! $loop->first) x-cloak @endif>
                <x-caption class="px-5 pt-5 pb-2">{{ __('Types in :category', ['category' => $category['name']]) }}</x-caption>

                <div class="border-t border-hairline">
                  @forelse ($category['types'] as $type)
                    <button type="button" x-on:click="pick({{ $type['id'] }}, @js($type['name']), @js($type['categoryName']), @js($type['fieldsUrl']))" class="{{ $row }}">
                      <x-section-mark :icon="$type['icon']" :colour="$type['colour']" />
                      <span class="min-w-0 flex-1 font-medium text-ink">{{ $type['name'] }}</span>
                      <span x-show="changing && type && type.id === {{ $type['id'] }}" x-cloak><x-badge>{{ __('Current') }}</x-badge></span>
                      <span class="text-sm text-muted">{{ $type['fieldCountLabel'] }}</span>
                      <span class="text-muted" aria-hidden="true"><x-icon name="chevron-right" :size="16" /></span>
                    </button>
                  @empty
                    <p class="px-5 py-3 text-sm text-muted">{{ __('No type in this category yet.') }}</p>
                  @endforelse
                </div>
              </div>
            @endforeach
          </div>
        </div>

        <div x-show="searching" x-cloak>
          <p class="px-5 pt-5 pb-2" aria-live="polite">
            <x-caption x-text="resultHeading"></x-caption>
          </p>

          <div class="border-t border-hairline">
            @foreach ($form->categories() as $category)
              @foreach ($category['types'] as $type)
                <button
                  type="button"
                  x-show="matches(@js($type['name']), @js($type['categoryName']))"
                  x-on:click="pick({{ $type['id'] }}, @js($type['name']), @js($type['categoryName']), @js($type['fieldsUrl']))"
                  class="{{ $row }}"
                >
                  <x-section-mark :icon="$type['icon']" :colour="$type['colour']" />
                  <span class="flex min-w-0 flex-1 flex-col">
                    <span class="font-medium text-ink">{{ $type['name'] }}</span>
                    <span class="text-sm text-muted">{{ $type['categoryName'] }}</span>
                  </span>
                  <span class="text-muted" aria-hidden="true"><x-icon name="chevron-right" :size="16" /></span>
                </button>
              @endforeach
            @endforeach
          </div>

          <div x-show="resultCount === 0" class="flex flex-col items-center gap-1.5 border-t border-hairline px-5 py-8 text-center">
            <p class="text-card-title text-ink" x-text="noMatchLine"></p>
            <p class="max-w-95 text-sm text-pretty text-muted">{{ __('Try a shorter word, or add the type in Settings. It will show up here straight away.') }}</p>
          </div>
        </div>
      @endif

      <div class="flex justify-end border-t border-hairline p-5">
        <x-link :href="$form->settingsUrl()" class="text-sm">{{ __('Manage life event types') }}</x-link>
      </div>
    </div>

    <div x-show="step === 'details'" x-cloak>
      <div class="flex flex-wrap items-center justify-between gap-3 border-b border-hairline p-5">
        <div class="flex min-w-0 items-center gap-3">
          @foreach ($form->categories() as $category)
            @foreach ($category['types'] as $type)
              <x-section-mark :icon="$type['icon']" :colour="$type['colour']" :size="32" x-show="type && type.id === {{ $type['id'] }}" x-cloak />
            @endforeach
          @endforeach

          <p class="flex min-w-0 flex-col">
            <span class="text-xs text-muted" x-text="type ? type.categoryName : ''"></span>
            <span class="font-medium text-ink" x-text="type ? type.name : ''"></span>
          </p>
        </div>

        <x-button.secondary type="button" size="small" x-on:click="changeType($el)">{{ __('Change type') }}</x-button.secondary>
      </div>

      <div class="flex flex-col gap-5 p-5">
        @if ($errors->hasAny(['life_event_type_id', 'participants', 'participants.*', 'fields']))
          <x-notice tone="danger">
            {{ $errors->first('life_event_type_id') ?: $errors->first('participants') ?: $errors->first('participants.*') ?: $errors->first('fields') }}
          </x-notice>
        @endif

        <div class="flex flex-wrap items-start gap-x-12 gap-y-7">
          <div class="w-60 max-w-full shrink-0">
            <x-input
              type="date"
              id="life-event-date"
              name="happened_on"
              :label="__('When did it happen')"
              x-model="date"
              :error="$errors->get('happened_on')"
            />
          </div>

          <div class="flex min-w-0 flex-1 flex-col gap-1.5">
            <span class="text-label text-ink">{{ __('Who was there') }}</span>

            <div class="flex flex-wrap items-center gap-2">
              @if ($form->choosesSubject())
                <span class="inline-flex h-8 items-center rounded-full border border-hairline bg-canvas px-3 text-sm font-medium text-body" x-text="subject.name">{{ $form->contactName() }}</span>
              @else
                <span class="inline-flex h-8 items-center gap-1.5 rounded-full border border-hairline bg-canvas pe-3 ps-1 text-sm font-medium text-body">
                  <x-contact-avatar :name="$form->contactName()" :size="24" />
                  {{ $form->contactName() }}
                </span>
              @endif

              <template x-for="person in people" x-bind:key="person.id">
                <span class="inline-flex h-8 items-center gap-1.5 rounded-full border border-hairline bg-canvas pe-1 ps-3 text-sm font-medium text-body">
                  <input type="hidden" name="participants[]" x-bind:value="person.id" />
                  <span x-text="person.name"></span>
                  <button type="button" x-on:click="remove(person.id)" x-bind:aria-label="copy.remove.replace(':name', person.name)" class="focus-ring flex size-6 items-center justify-center rounded-full text-muted hover:bg-hover hover:text-ink">
                    <x-icon name="close" :size="14" />
                  </button>
                </span>
              </template>

              <button type="button" x-show="! adding" x-on:click="startAdding()" class="{{ $chip }}">
                <x-icon name="plus" :size="15" />
                {{ __('Add someone') }}
              </button>
            </div>

            <p class="text-xs text-muted">{{ __('It shows on the timeline of everyone listed.') }}</p>

            <div x-show="adding" x-cloak class="flex max-w-90 flex-col gap-1.5">
              <div class="flex gap-2">
                <x-input
                  type="search"
                  id="life-event-person-search"
                  size="small"
                  :placeholder="__('Search the people in this vault')"
                  aria-label="{{ __('Search the people in this vault') }}"
                  x-model="personQuery"
                  x-on:input.debounce.400ms="searchPeople()"
                  x-on:keydown.enter.prevent="searchPeople()"
                  autocomplete="off"
                />

                <x-button.ghost type="button" size="small" x-on:click="adding = false">{{ __('Done') }}</x-button.ghost>
              </div>

              <div id="life-event-candidates" x-merge="replace" aria-live="polite" class="dim-when-busy"></div>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-3.5">
          <div class="flex flex-col gap-0.5">
            <span class="text-label text-ink">{{ __('Details') }}</span>
            <span class="text-xs text-muted">{{ __('Optional. Add only what you want to remember.') }}</span>
          </div>

          @foreach ([
            'title' => __('Summary'),
            'narrative' => __('Description'),
            'location' => __('Place'),
            'duration_in_minutes' => __('Duration in minutes'),
          ] as $piece => $label)
            <fieldset x-show="isOpen('{{ $piece }}')" x-bind:disabled="! isOpen('{{ $piece }}')" x-cloak class="relative">
              @if ($piece === 'narrative')
                <x-textarea id="life-event-narrative" name="narrative" :label="$label" :value="$form->pieceValue('narrative')" :placeholder="__('Anything worth remembering')" :error="$errors->get('narrative')" />
              @elseif ($piece === 'duration_in_minutes')
                <div class="max-w-50">
                  <x-input type="number" id="life-event-duration" name="duration_in_minutes" :label="$label" :value="$form->pieceValue('duration_in_minutes')" min="1" :max="\App\Helpers\Duration::LONGEST_IN_MINUTES" :error="$errors->get('duration_in_minutes')" />
                </div>
              @else
                <x-input
                  :id="'life-event-'.$piece"
                  :name="$piece"
                  :label="$label"
                  :value="$form->pieceValue($piece)"
                  :placeholder="$piece === 'title' ? __('One line, as it would read on the timeline') : __('A town, an address, a venue')"
                  :error="$errors->get($piece)"
                />
              @endif

              <button type="button" x-on:click="closePiece('{{ $piece }}')" aria-label="{{ __('Remove :name', ['name' => $label]) }}" class="focus-ring absolute top-0 end-0 rounded-sm text-xs font-medium text-muted hover:text-ink hover:underline">{{ __('Remove') }}</button>
            </fieldset>
          @endforeach

          @include('app.vaults.contacts._life-event-fields', [
            'lifeEventType' => $oldType,
            'fields' => $oldType ? $form->fieldsOf($oldType) : [],
            'vault' => $form->vault(),
          ])

          <div class="flex flex-wrap gap-2">
            @foreach ([
              'title' => __('Summary'),
              'narrative' => __('Description'),
              'location' => __('Place'),
              'duration_in_minutes' => __('Duration'),
            ] as $piece => $label)
              <button type="button" x-show="! isOpen('{{ $piece }}')" x-on:click="openPiece('{{ $piece }}')" class="{{ $chip }}">
                <x-icon name="plus" :size="15" />
                {{ $label }}
              </button>
            @endforeach
          </div>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-3 border-t border-hairline p-5">
        <x-button.secondary type="button" x-on:click="cancel($el)">{{ __('Cancel') }}</x-button.secondary>

        <x-button x-bind:disabled="! date">
          @unless ($form->isEditing())
            <x-slot:icon><x-icon name="plus" :size="16" /></x-slot:icon>
          @endunless
          {{ $form->submitLabel() }}
        </x-button>
      </div>
    </div>
  </x-form>
</div>
