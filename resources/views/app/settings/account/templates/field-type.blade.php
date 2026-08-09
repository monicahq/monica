{{--
  Picking what kind of field to add.

  The type is the one decision about a field that cannot be taken back, so this
  is a screen of its own rather than a dropdown: each type says what it is for
  and gives an example, and the label is asked for at the same time so the field
  arrives named rather than as "Untitled".

  The types the first version does not ship are shown and cannot be picked.
  Somebody deciding how to record a price is better served by reading that money
  is coming than by inventing a workaround nobody will revisit.

  @var \App\Models\Template $template
  @var \App\Models\TemplateVersionSection $row
  @var string $sectionName
  @var \App\ViewModels\Settings\Account\Templates\FieldCatalogueViewModel $catalogue
  @var \App\Models\User $user
--}}
<x-app-layout :title="__('Add a field')">
  <x-slot:sidebar>
    <x-settings.sidebar :user="$user" current="templates" />
  </x-slot:sidebar>

  <x-slot:breadcrumb>
    <nav class="text-sm text-muted" aria-label="{{ __('Breadcrumb') }}">
      <a href="{{ route('settings.templates.index') }}" data-turbo="true" class="hover:text-ink">{{ __('Templates') }}</a>
      <span class="px-1 text-placeholder" aria-hidden="true">/</span>
      <a href="{{ route('settings.templateBuilder.index', $template) }}" data-turbo="true" class="hover:text-ink">{{ $template->name }}</a>
      <span class="px-1 text-placeholder" aria-hidden="true">/</span>
      <span class="text-ink" aria-current="page">{{ __('Add a field') }}</span>
    </nav>
  </x-slot:breadcrumb>

  <div class="space-y-1.5">
    <a
      href="{{ route('settings.templateBuilder.index', $template) }}"
      data-turbo="true"
      class="-ml-1.5 inline-flex items-center gap-1.5 rounded-md px-1.5 py-1 text-sm text-muted transition-colors duration-150 hover:bg-hover hover:text-ink"
    >
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M9.6 3.6 5.2 8l4.4 4.4"></path>
      </svg>
      {{ __('Back to the template') }}
    </a>

    <x-page-header
      :title="__('What goes in :section?', ['section' => $sectionName])"
      :description="__('The type decides what people can type, how it is checked and how it is searched. Pick the closest one. It is settled when the field is added, so everything else about the field can be changed afterwards but this cannot.')"
      class="max-w-160"
    />
  </div>

  <x-form
    method="post"
    :action="route('settings.fields.create', [$template, $row])"
    x-data="{
      type: @js(old('type', '')),
      needsAList: @js($catalogue->typesNeedingAValueList()),
      get offersChoices() { return this.needsAList.includes(this.type) },
    }"
    class="space-y-7"
  >
    <x-box :title="__('What is it called?')">
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
    </x-box>

    <div class="space-y-1.5">
      <p class="text-sm font-semibold text-ink">{{ __('And what kind of thing is it?') }}</p>

      <x-error :messages="$errors->get('type')" />
      <x-error :messages="$errors->get('value_list_id')" />
    </div>

    @foreach ($catalogue->families() as $family)
      <div class="space-y-2.5">
        <div class="flex flex-wrap items-baseline gap-2.5">
          <h2 class="text-lg font-semibold text-ink">{{ $family['name'] }}</h2>

          <span class="text-sm text-muted">{{ $family['blurb'] }}</span>
        </div>

        <div class="grid gap-2.5 sm:grid-cols-2">
          @foreach ($family['types'] as $type)
            @if ($type['isAvailable'])
              <label class="grid cursor-pointer content-start gap-1.5 rounded-xl border border-hairline bg-canvas p-3.5 transition-colors duration-150 hover:bg-hover has-checked:border-primary has-checked:bg-sunken">
                <span class="flex items-center gap-2">
                  <input type="radio" name="type" value="{{ $type['key'] }}" x-model="type" @checked(old('type') === $type['key']) required />

                  <span class="text-sm font-semibold text-ink">{{ $type['label'] }}</span>

                  <span class="ml-auto font-mono text-xs text-muted-soft">{{ $type['key'] }}</span>
                </span>

                <span class="text-sm leading-relaxed text-body">{{ $type['description'] }}</span>

                <span class="border-t border-hairline-soft pt-1.5 text-xs leading-relaxed text-muted">{{ $type['example'] }}</span>
              </label>
            @else
              <div class="grid content-start gap-1.5 rounded-xl border border-hairline bg-sunken p-3.5 opacity-70">
                <span class="flex items-center gap-2">
                  <span class="text-sm font-semibold text-ink">{{ $type['label'] }}</span>

                  <span class="rounded-sm border border-dashed border-muted px-1.5 py-px text-xs text-muted">{{ __('Later') }}</span>

                  <span class="ml-auto font-mono text-xs text-muted-soft">{{ $type['key'] }}</span>
                </span>

                <span class="text-sm leading-relaxed text-body">{{ $type['description'] }}</span>

                <span class="border-t border-hairline-soft pt-1.5 text-xs leading-relaxed text-muted">{{ $type['example'] }}</span>
              </div>
            @endif
          @endforeach
        </div>
      </div>
    @endforeach

    <div x-cloak x-show="offersChoices">
      <x-box :title="__('Where do the choices come from?')">
        <div class="space-y-3">
          <p class="max-w-140 text-sm leading-relaxed text-body">{{ __('A field that offers choices reads them from a value list, so the same options can be reused everywhere rather than retyped into every field.') }}</p>

          @if ($valueLists->isEmpty())
            <p class="text-sm text-warning">{{ __('You have no value lists yet. Make one on the default values screen, then come back.') }}</p>
          @else
            <div class="max-w-96 space-y-1.5">
              <x-label for="value_list_id" :value="__('Value list')" />

              <select
                id="value_list_id"
                name="value_list_id"
                ::required="offersChoices"
                class="block w-full appearance-none rounded-md border border-hairline-strong bg-input px-3 py-2.5 text-base text-ink sm:text-sm"
              >
                <option value="">{{ __('Pick a list') }}</option>

                @foreach ($valueLists as $list)
                  <option value="{{ $list->id }}" @selected(old('value_list_id') === (string) $list->id)>{{ $list->name }}</option>
                @endforeach
              </select>
            </div>
          @endif
        </div>
      </x-box>
    </div>

    <div class="sticky bottom-0 flex flex-wrap items-center gap-3 border-t border-hairline bg-page/90 py-3.5 backdrop-blur-md">
      <p class="text-sm text-muted">{{ __('Everything else about the field is set once it exists.') }}</p>

      <div class="ml-auto flex items-center gap-3">
        <x-button.secondary :href="route('settings.templateBuilder.index', $template)" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

        <x-button>{{ __('Add the field') }}</x-button>
      </div>
    </div>
  </x-form>
</x-app-layout>
