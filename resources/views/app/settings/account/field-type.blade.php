{{-- Picking what kind of field to add. --}}
{{--
  @var \App\Models\StructureVersionSection $row
  @var string $sectionName
  @var \App\Models\Field|null $parent
  @var string $backHref
  @var string $backLabel
  @var string $current
  @var string $action
  @var \App\ViewModels\Settings\Account\Templates\FieldCatalogueViewModel $catalogue
  @var \Illuminate\Support\Collection<int, \App\Models\ValueList> $valueLists
  @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\EntityType> $entityTypes
  @var \App\Models\User $user
--}}
@php
  $parent ??= null;
  $types = $catalogue->types();
  $pickable = array_values(array_map(fn (array $type): string => $type['key'], array_filter($types, fn (array $type): bool => $type['isAvailable'])));
  $footerLines = $catalogue->footerLines($sectionName);
  $nothingPicked = __('Pick a type to carry on.');
@endphp
<x-settings.page
  :title="__('Add a field')"
  :user="$user"
  :current="$current"
  :back-href="$backHref"
  :back-label="$backLabel"
  wide
>
  <x-slot:footer>
    <div
      x-data="{
        type: {{ Js::from(old('type', '')) }},
        pickable: {{ Js::from($pickable) }},
        get canAdd() { return this.pickable.includes(this.type) },
      }"
      @field-type-picked.window="type = $event.detail"
      class="flex w-full flex-wrap items-center gap-3"
    >
      <div class="min-w-0 text-sm text-pretty text-muted">
        <p x-show="!canAdd">{{ $nothingPicked }}</p>

        @foreach ($footerLines as $key => $line)
          <p x-cloak x-show="canAdd && type === '{{ $key }}'">{{ $line }}</p>
        @endforeach
      </div>

      <div class="ml-auto flex items-center gap-3">
        <x-button.secondary :href="$backHref" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

        <x-button form="add-field" ::disabled="!canAdd">{{ __('Add the field') }}</x-button>
      </div>
    </div>
  </x-slot:footer>

  <x-form
    id="add-field"
    method="post"
    :action="$action"
    x-effect="$dispatch('field-type-picked', type)"
    x-init="$watch('type', (picked) => { if (! listPicked) listId = String(suppliedLists[picked] ?? '') })"
    x-data="{
      type: {{ Js::from(old('type', '')) }},
      peek: '',
      query: '',
      labelValue: {{ Js::from(old('label', '')) }},
      index: {{ Js::from($catalogue->searchIndex()) }},
      sentences: {{ Js::from($catalogue->sentences()) }},
      countPhrases: {{ Js::from($catalogue->countPhrases()) }},
      totalPhrase: {{ Js::from($catalogue->totalPhrase()) }},
      pickable: {{ Js::from($pickable) }},
      needsAList: {{ Js::from($catalogue->typesNeedingAValueList()) }},
      offersALabelList: {{ Js::from($catalogue->typesOfferingALabelList()) }},
      suppliedLists: {{ Js::from($catalogue->suppliedLabelListIds($valueLists)) }},
      listId: {{ Js::from(old('value_list_id', '')) }},
      listPicked: {{ Js::from(old('value_list_id', '') !== '') }},
      relationTypes: {{ Js::from($catalogue->relationTypes()) }},
      get needle() { return this.query.trim().toLowerCase() },
      matches(key) { return this.needle === '' || this.index[key].includes(this.needle) },
      get shown() { return Object.keys(this.index).filter(key => this.matches(key)).length },
      get countLine() { return this.needle === '' ? this.totalPhrase : this.countPhrases[this.shown] },
      get previewed() { return this.peek !== '' ? this.peek : this.type },
      get canAdd() { return this.pickable.includes(this.type) },
      get offersChoices() { return this.needsAList.includes(this.type) },
      get offersALabel() { return this.offersALabelList.includes(this.type) },
      get offersAList() { return this.offersChoices || this.offersALabel },
      get isRelation() { return this.relationTypes.includes(this.type) },
    }"
    class="flex flex-col gap-7"
  >
    @if ($parent !== null)
      <input type="hidden" name="parent_id" value="{{ $parent->id }}">
    @endif

    <x-page-header :title="$parent === null ? __('What goes in :section?', ['section' => $sectionName]) : __('What goes inside :repeater?', ['repeater' => $parent->label])" :description="__('The type decides what people can write, how it is checked and how it is searched. Pick the closest one. The panel shows how it appears on a record.')" />

    <section class="flex flex-col gap-3">
      <h2 class="text-section-title text-ink">{{ __('What is it called?') }}</h2>

      <x-panel>
        <x-input
          id="label"
          name="label"
          :value="old('label')"
          :label="__('Label')"
          :placeholder="__('Weight')"
          :help="__('What somebody sees next to the input on the record.')"
          :error="$errors->get('label')"
          class="max-w-96"
          maxlength="255"
          required
          autofocus
          x-model="labelValue"
        />
      </x-panel>
    </section>

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
      <section class="flex min-w-0 flex-col gap-4">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
          <h2 class="shrink-0 text-section-title text-ink">{{ __('And what kind of thing is it?') }}</h2>

          <span class="shrink-0 text-xs text-muted" x-text="countLine">{{ $catalogue->totalPhrase() }}</span>

          <label class="relative ml-auto flex h-8 min-w-0 flex-1 items-center gap-2 rounded-md border border-hairline bg-card px-2.5 transition-colors duration-(--duration-fast) ease-out focus-within:border-hairline-strong focus-within:ring-[3px] focus-within:ring-focus-ring/45 sm:max-w-60 sm:flex-none">
            <span class="sr-only">{{ __('Filter the types') }}</span>

            <x-icon name="search" :size="14" class="shrink-0 text-muted" />

            <input
              type="search"
              x-model="query"
              placeholder="{{ __('Filter the types') }}"
              class="w-full min-w-0 border-0 bg-transparent p-0 font-sans text-[16px] text-ink placeholder-placeholder focus:outline-none sm:text-sm"
            />
          </label>
        </div>

        <x-error :messages="$errors->get('type')" />
        <x-error :messages="$errors->get('value_list_id')" />

        <fieldset class="relative flex min-w-0 flex-col gap-4">
          <legend class="sr-only">{{ __('And what kind of thing is it?') }}</legend>

          @foreach ($catalogue->families() as $family)
            @php
              $familyKeys = array_map(fn (array $type): string => $type['key'], $family['types']);
            @endphp
            <div class="flex flex-col gap-2" x-show="{{ Js::from($familyKeys) }}.some(key => matches(key))">
              <div class="flex flex-wrap items-baseline gap-x-3 gap-y-0.5 border-b border-hairline-soft pb-1.5">
                <h3 class="shrink-0 text-label tracking-caption text-ink uppercase">{{ $family['name'] }}</h3>
              </div>

              <div class="grid grid-cols-[repeat(auto-fill,minmax(10.5rem,1fr))] gap-1.5">
                @foreach ($family['types'] as $type)
                  @if ($type['isAvailable'])
                    <label
                      x-show="matches('{{ $type['key'] }}')"
                      @mouseenter="peek = '{{ $type['key'] }}'"
                      @mouseleave="peek = ''"
                      class="relative flex cursor-pointer items-center gap-2 rounded-md border border-hairline bg-canvas px-2.5 py-2 transition-colors duration-(--duration-fast) ease-out hover:bg-hover has-checked:border-ink has-checked:bg-sunken has-focus-visible:ring-[3px] has-focus-visible:ring-focus-ring/45"
                    >
                      <input
                        type="radio"
                        name="type"
                        value="{{ $type['key'] }}"
                        x-model="type"
                        @focus="peek = '{{ $type['key'] }}'"
                        @blur="peek = ''"
                        @checked(old('type') === $type['key'])
                        required
                        class="peer sr-only"
                      />

                      <x-icon :name="$type['mark']" :size="16" class="text-muted peer-checked:text-ink" />

                      <span class="min-w-0 truncate text-sm text-ink peer-checked:font-semibold">{{ $type['label'] }}</span>

                      <x-icon name="check" :size="14" :stroke-width="1.8" class="invisible ml-auto text-ink peer-checked:visible" />
                    </label>
                  @else
                    <button
                      type="button"
                      aria-disabled="true"
                      x-show="matches('{{ $type['key'] }}')"
                      @mouseenter="peek = '{{ $type['key'] }}'"
                      @mouseleave="peek = ''"
                      @focus="peek = '{{ $type['key'] }}'"
                      @blur="peek = ''"
                      class="flex cursor-not-allowed items-center gap-2 rounded-md border border-hairline bg-card px-2.5 py-2 text-left focus-ring"
                    >
                      <x-icon :name="$type['mark']" :size="16" class="text-muted-soft" />

                      <span class="min-w-0 truncate text-sm text-muted">{{ $type['label'] }}</span>

                      <x-badge tone="info" class="ml-auto shrink-0">{{ __('Later') }}</x-badge>
                    </button>
                  @endif
                @endforeach
              </div>
            </div>
          @endforeach
        </fieldset>

        <div x-show="shown === 0" x-cloak class="flex flex-wrap items-center gap-3">
          <p class="text-sm text-pretty text-muted">{{ __('Nothing matches that.') }}</p>

          <x-button.ghost type="button" size="small" @click="query = ''">{{ __('Clear the filter') }}</x-button.ghost>
        </div>
      </section>

      <aside class="relative lg:sticky lg:top-0" x-cloak>
        <p class="sr-only" aria-live="polite" x-text="previewed === '' ? '' : sentences[previewed]"></p>

        <div class="overflow-hidden rounded-lg border border-hairline bg-canvas">
          <x-empty-state x-show="previewed === ''" :title="__('Nothing picked yet')" class="bg-sunken">
            <x-slot:icon><x-icon name="file" :size="30" :stroke-width="1.4" /></x-slot:icon>

            {{ __('Pick a type and this shows the field it produces, filled in.') }}
          </x-empty-state>

          @foreach ($types as $type)
            <div x-show="previewed === '{{ $type['key'] }}'">
              <div class="flex items-center gap-2.5 border-b border-hairline-soft bg-sunken px-4 py-3">
                <span class="flex size-6.5 shrink-0 items-center justify-center rounded-xs border border-hairline bg-canvas text-ink">
                  <x-icon :name="$type['mark']" :size="15" />
                </span>

                <span class="grid min-w-0">
                  <span class="truncate text-card-title text-ink">{{ $type['label'] }}</span>

                  <code class="truncate font-mono text-xs text-muted-soft">{{ $type['key'] }}</code>
                </span>

                @unless ($type['isAvailable'])
                  <x-badge tone="info" class="ml-auto shrink-0">{{ __('Later') }}</x-badge>
                @endunless
              </div>

              <div class="flex flex-col gap-3.5 p-4">
                <p class="text-sm text-pretty text-body">{{ $type['description'] }}</p>

                <div class="flex flex-col gap-2 rounded-md border border-hairline-soft bg-sunken p-3">
                  <x-caption tone="muted">{{ __('On the record') }}</x-caption>

                  <span class="text-sm font-semibold text-ink" x-text="labelValue.trim() === '' ? {{ Js::from($type['label']) }} : labelValue.trim()"></span>

                  @include('app.settings.account.templates._field-type-preview', ['preview' => $type['preview']])
                </div>

                <p class="text-xs text-pretty text-muted">{{ __('For example: :example.', ['example' => $type['example']]) }}</p>
              </div>
            </div>
          @endforeach
        </div>
      </aside>
    </div>

    <section x-cloak x-show="offersAList" class="flex flex-col gap-3">
      <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
        <h2 class="shrink-0 text-section-title text-ink">
          <span x-cloak x-show="! offersALabel">{{ __('Where do the choices come from?') }}</span>
          <span x-cloak x-show="offersALabel">{{ __('Where do the labels come from?') }}</span>
        </h2>

        <p x-cloak x-show="! offersALabel" class="text-sm text-pretty text-muted">{{ __('A field that offers choices reads them from a value list. The same options can then be reused everywhere, instead of retyped into every field.') }}</p>

        <p x-cloak x-show="offersALabel" class="text-sm text-pretty text-muted">{{ __('The label says what a value is for, such as mobile or work. Leave it empty and the field takes no label.') }}</p>
      </div>

      <x-panel>
        @if ($valueLists->isEmpty())
          <x-notice tone="warning">{{ __('You have no value lists yet. Make one on the value lists screen, then come back.') }}</x-notice>
        @else
          <x-select id="value_list_id" :label="__('Value list')" ::required="offersChoices" x-model="listId" @change="listPicked = true" class="max-w-96">
            <option value="">{{ __('Pick a list') }}</option>

            @foreach ($valueLists as $list)
              <option value="{{ $list->id }}" @selected(old('value_list_id') === (string) $list->id)>{{ $list->name }}</option>
            @endforeach
          </x-select>
        @endif
      </x-panel>
    </section>

    <section x-cloak x-show="isRelation" class="flex flex-col gap-3">
      <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
        <h2 class="shrink-0 text-section-title text-ink">{{ __('What does it point at?') }}</h2>

        <p class="text-sm text-pretty text-muted">{{ __('A relation names somebody in the vault, or a record of one of your kinds of record. This is fixed once the field is added.') }}</p>
      </div>

      <x-panel>
        <x-select id="relation_target" :label="__('Points at')" ::required="isRelation" :error="$errors->get('relation_target')" class="max-w-96">
          @foreach ($catalogue->relationTargets($entityTypes) as $value => $label)
            <option value="{{ $value }}" @selected(old('relation_target', 'contact') === $value)>{{ $label }}</option>
          @endforeach
        </x-select>
      </x-panel>
    </section>
  </x-form>
</x-settings.page>
