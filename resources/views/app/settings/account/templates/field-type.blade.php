{{--
  Picking what kind of field to add.

  The type is the one decision about a field that cannot be taken back, so this
  is a screen of its own rather than a dropdown: each type says what it is for
  and gives an example, and the label is asked for at the same time so the field
  arrives named rather than as "Untitled".

  It asks for `wide` because the types are read by comparing them, and two
  columns of cards is what lets somebody scan a family at once.

  The types the first version does not ship are shown and cannot be picked.
  Somebody deciding how to record a price is better served by reading that money
  is coming than by inventing a workaround nobody will revisit.

  @var \App\Models\Template $template
  @var \App\Models\TemplateVersionSection $row
  @var string $sectionName
  @var \App\ViewModels\Settings\Account\Templates\FieldCatalogueViewModel $catalogue
  @var \App\Models\User $user
--}}
@php
  $backToBuilder = route('settings.templateBuilder.index', ['template' => $template, 'page' => $row->templateVersionPage->key]);
@endphp
<x-shell :title="__('Add a field')" wide>
  <x-slot:bar>
    <x-button.secondary :href="$backToBuilder" data-turbo="true" size="small">
      <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />
      {{ __('Back to the template') }}
    </x-button.secondary>
  </x-slot:bar>

  <x-slot:dock>
    <x-dock :user="$user" />
  </x-slot:dock>

  <header class="flex flex-col gap-2">
    <h1 class="font-display text-page-title text-ink">{{ __('What goes in :section?', ['section' => $sectionName]) }}</h1>

    <p class="max-w-[64ch] text-base text-pretty text-body">{{ __('The type decides what people can type, how it is checked and how it is searched. Pick the closest one. It is settled when the field is added, so everything else about the field can be changed afterwards but this cannot.') }}</p>
  </header>

  <x-form
    method="post"
    :action="route('settings.fields.create', [$template, $row])"
    x-data="{
      type: @js(old('type', '')),
      needsAList: @js($catalogue->typesNeedingAValueList()),
      get offersChoices() { return this.needsAList.includes(this.type) },
    }"
    class="flex flex-col gap-8"
  >
    <section class="flex flex-col gap-3">
      <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
        <h2 class="shrink-0 font-display text-section-title text-ink">{{ __('What is it called?') }}</h2>
      </div>

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
        />
      </x-panel>
    </section>

    <section class="flex flex-col gap-6">
      <div class="flex flex-col gap-1.5 px-0.5">
        <h2 class="font-display text-section-title text-ink">{{ __('And what kind of thing is it?') }}</h2>

        <x-error :messages="$errors->get('type')" />
        <x-error :messages="$errors->get('value_list_id')" />
      </div>

      @foreach ($catalogue->families() as $family)
        <div class="flex flex-col gap-2.5">
          <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
            <h3 class="shrink-0 text-card-title text-ink">{{ $family['name'] }}</h3>

            <p class="text-sm text-pretty text-muted">{{ $family['blurb'] }}</p>
          </div>

          <div class="grid gap-2.5 sm:grid-cols-2">
            @foreach ($family['types'] as $type)
              @if ($type['isAvailable'])
                <label class="overflow-hidden rounded-lg border border-hairline bg-canvas shadow-raised grid cursor-pointer content-start gap-1.5 p-3.5 transition-colors duration-[110ms] hover:bg-hover has-checked:border-ink has-checked:bg-sunken">
                  <span class="flex items-center gap-2">
                    <input type="radio" name="type" value="{{ $type['key'] }}" x-model="type" @checked(old('type') === $type['key']) required />

                    <span class="text-base font-semibold text-ink">{{ $type['label'] }}</span>

                    <code class="ml-auto font-mono text-[12.5px] text-muted">{{ $type['key'] }}</code>
                  </span>

                  <span class="text-sm text-pretty text-muted">{{ $type['description'] }}</span>

                  <span class="text-xs text-pretty text-muted border-t border-hairline pt-1.5">{{ $type['example'] }}</span>
                </label>
              @else
                <div class="overflow-hidden rounded-lg border border-hairline bg-sand grid content-start gap-1.5 p-3.5">
                  <span class="flex items-center gap-2">
                    <span class="text-base font-semibold text-ink">{{ $type['label'] }}</span>

                    <x-badge tone="outline">{{ __('Later') }}</x-badge>

                    <code class="ml-auto font-mono text-[12.5px] text-muted">{{ $type['key'] }}</code>
                  </span>

                  <span class="text-sm text-pretty text-muted">{{ $type['description'] }}</span>

                  <span class="text-xs text-pretty text-muted border-t border-hairline pt-1.5">{{ $type['example'] }}</span>
                </div>
              @endif
            @endforeach
          </div>
        </div>
      @endforeach
    </section>

    <section x-cloak x-show="offersChoices" class="flex flex-col gap-3">
      <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
        <h2 class="shrink-0 font-display text-section-title text-ink">{{ __('Where do the choices come from?') }}</h2>

        <p class="text-sm text-pretty text-muted">{{ __('A field that offers choices reads them from a value list, so the same options can be reused everywhere rather than retyped into every field.') }}</p>
      </div>

      <x-panel>
        @if ($valueLists->isEmpty())
          <x-notice tone="warning">{{ __('You have no value lists yet. Make one on the default values screen, then come back.') }}</x-notice>
        @else
          <x-select id="value_list_id" :label="__('Value list')" ::required="offersChoices" class="max-w-96">
            <option value="">{{ __('Pick a list') }}</option>

            @foreach ($valueLists as $list)
              <option value="{{ $list->id }}" @selected(old('value_list_id') === (string) $list->id)>{{ $list->name }}</option>
            @endforeach
          </x-select>
        @endif
      </x-panel>
    </section>

    <div class="sticky bottom-0 flex flex-wrap items-center gap-3 border-t border-hairline bg-page/90 py-3.5 backdrop-blur-md">
      <p class="text-sm text-pretty text-muted">{{ __('Everything else about the field is set once it exists.') }}</p>

      <div class="ml-auto flex items-center gap-3">
        <x-button.secondary :href="$backToBuilder" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

        <x-button>{{ __('Add the field') }}</x-button>
      </div>
    </div>
  </x-form>
</x-shell>
