{{--
  The form that names a template, and the same form that renames one.

  It moves onto the new shell with the list, because "Create a template" leads
  straight here.

  What the template applies to is asked for once, while it is being created. A
  template describes a kind of record, and pointing an existing one somewhere
  else would leave every record already on it rendering something never meant
  for it, so the choice disappears once it has been made.

  @var \App\ViewModels\Settings\Account\Templates\TemplateFormViewModel $viewModel
--}}
@php
  $values = $viewModel->values();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:bar>
    <a href="{{ route('settings.templates.index') }}" data-turbo="true" class="erica-btn erica-btn--secondary erica-btn--sm">
      <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />
      {{ __('Back to the templates') }}
    </a>
  </x-slot:bar>

  <x-slot:dock>
    <x-dock :user="$viewModel->user()" />
  </x-slot:dock>

  <header class="flex flex-col gap-2">
    <h1 class="erica-page-title">{{ $viewModel->title() }}</h1>

    <p class="erica-lede max-w-[56ch]">{{ $viewModel->description() }}</p>
  </header>

  <div
    x-data="{
      name: @js($values['name']),
      slug: @js($values['slug']),
      slugTouched: @js($values['slug'] !== ''),
      appliesTo: @js($values['appliesTo']),
      get named() { return this.name.trim() !== '' },
    }"
    @if ($viewModel->slugIsEditable())
      x-effect="if (! slugTouched) slug = window.slugify(name)"
    @endif
    class="flex flex-col gap-8"
  >
    <x-form id="template-form" :method="$viewModel->method()" :action="$viewModel->action()" class="flex flex-col gap-8">
      <section class="flex flex-col gap-3">
        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
          <h2 class="erica-section-title shrink-0">{{ __('Name') }}</h2>

          <p class="erica-small">{{ __('The name is what the picker shows when somebody makes a record. The slug is what the URL and the API use.') }}</p>
        </div>

        <x-panel>
          <div class="flex flex-col gap-4.5">
            <x-input
              id="name"
              x-model="name"
              :value="$values['name']"
              :label="__('Name')"
              :placeholder="__('Pet')"
              :error="$errors->get('name')"
              maxlength="255"
              required
            />

            @if ($viewModel->slugIsEditable())
              <x-input
                id="slug"
                x-model="slug"
                @input="slugTouched = $event.target.value.trim() !== ''"
                @blur="slug = window.slugify(slug)"
                :value="$values['slug']"
                :label="__('Slug')"
                :placeholder="__('pet')"
                :help="$viewModel->slugHelp()"
                :error="$errors->get('slug')"
                :maxlength="\App\Models\Template::SLUG_LENGTH"
                class="font-mono"
              />
            @else
              {{--
                Once the slug is settled it stops being a field. A disabled box invites
                clicking and submits nothing, so what is shown instead is the value
                itself, in mono, the way every machine value in the product is written.
              --}}
              <div class="erica-field">
                <p class="erica-field__label">{{ __('Slug') }}</p>

                <p class="rounded-md border border-hairline bg-sunken px-3 py-2 font-mono text-sm break-all text-ink select-all">{{ $values['slug'] }}</p>

                <p class="erica-field__help">{{ $viewModel->slugHelp() }}</p>
              </div>
            @endif

            <x-textarea
              id="description"
              :label="__('Description')"
              optional
              :value="$values['description']"
              :placeholder="__('Animals, with the health and care blocks a vet visit needs.')"
              :help="__('Shown under the name on the list of templates.')"
              :error="$errors->get('description')"
            />
          </div>
        </x-panel>
      </section>

      @unless ($viewModel->isEdit())
        <section class="flex flex-col gap-3">
          <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
            <h2 class="erica-section-title shrink-0">{{ __('What it describes') }}</h2>

            <p class="erica-small">{{ __('Settled now and not changeable later, since every record made with it would be left rendering something else.') }}</p>
          </div>

          <x-panel :flush="true">
            <fieldset>
              <legend class="sr-only">{{ __('What it describes') }}</legend>

              <label class="erica-row erica-row--top erica-row--interactive">
                <input type="radio" name="applies_to" value="contact" x-model="appliesTo" class="mt-1" />

                <span class="erica-row__main">
                  <span class="erica-row__title block">{{ __('A contact') }}</span>
                  <span class="erica-row__desc block">{{ __('The people every vault keeps.') }}</span>
                </span>
              </label>

              @if ($viewModel->hasEntityTypes())
                <label class="erica-row erica-row--top erica-row--interactive">
                  <input type="radio" name="applies_to" value="entity" x-model="appliesTo" class="mt-1" />

                  <span class="erica-row__main">
                    <span class="erica-row__title block">{{ __('A kind of record you invented') }}</span>
                    <span class="erica-row__desc block">{{ __('Animals, companies, wine bottles, whatever you named.') }}</span>
                  </span>
                </label>
              @endif
            </fieldset>

            @if ($viewModel->hasEntityTypes())
              <div x-cloak x-show="appliesTo === 'entity'" class="erica-field border-t border-hairline px-4 py-4">
                <x-label for="entity_type_id" :value="__('Which kind')" />

                <select id="entity_type_id" name="entity_type_id" class="erica-control erica-control--select">
                  <option value="">{{ __('Pick one') }}</option>

                  @foreach ($viewModel->entityTypes() as $type)
                    <option value="{{ $type['id'] }}" @selected($values['entityTypeId'] === (string) $type['id'])>{{ $type['name'] }}</option>
                  @endforeach
                </select>

                <x-error :messages="$errors->get('entity_type_id')" />
              </div>
            @else
              <div class="border-t border-hairline px-4 py-4">
                <x-notice>{{ __('You have not invented any kinds of record yet, so a template can only describe a contact for now.') }}</x-notice>
              </div>
            @endif

            <x-slot:footer>
              <x-error :messages="$errors->get('applies_to')" />
            </x-slot:footer>
          </x-panel>
        </section>
      @endunless
    </x-form>

    <div class="flex flex-wrap items-center gap-3.5">
      @if ($viewModel->isEdit())
        <x-modal.warning
          :title="__('Delete “:name”?', ['name' => $values['name']])"
          :confirm="__('Delete the template')"
          :action="route('settings.templates.destroy', $viewModel->template())"
        >
          <x-slot:trigger>
            <x-button.secondary type="button" @click="open = true" class="text-error">
              {{ __('Delete the template') }}
            </x-button.secondary>
          </x-slot:trigger>

          <p>{{ __('Every version of it goes, and the slug stays taken so an old link cannot land on something else.') }}</p>

          <p>{{ __('This is refused while any record still points at one of its versions.') }}</p>
        </x-modal.warning>
      @endif

      <div class="flex flex-wrap items-center gap-3 max-sm:w-full sm:ml-auto">
        <x-button.secondary :href="route('settings.templates.index')" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

        <x-button form="template-form" ::disabled="! named">{{ $viewModel->saveLabel() }}</x-button>
      </div>
    </div>
  </div>
</x-shell>
