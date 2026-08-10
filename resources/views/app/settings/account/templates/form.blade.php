{{--
  The form that names a template, and the same form that renames one.

  What the template applies to is asked for once, while it is being created. A
  template describes a kind of record, and pointing an existing one somewhere
  else would leave every record already on it rendering something never meant
  for it, so the choice disappears once it has been made.

  @var \App\ViewModels\Settings\Account\Templates\TemplateFormViewModel $viewModel
--}}
@php
  $values = $viewModel->values();
  $panel = 'grid gap-x-9 gap-y-5 md:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)]';
@endphp
<x-app-layout :title="$viewModel->title()">
  <x-slot:sidebar>
    <x-settings.sidebar :user="$viewModel->user()" current="templates" />
  </x-slot:sidebar>

  <x-slot:breadcrumb>
    <x-breadcrumb
      :items="[
        ['label' => __('Settings')],
        ['label' => __('Templates'), 'url' => route('settings.templates.index')],
        ['label' => $viewModel->isEdit() ? $values['name'] : __('New template')],
      ]"
    />
  </x-slot:breadcrumb>

  <div class="space-y-1.5">
    <a
      href="{{ route('settings.templates.index') }}"
      data-turbo="true"
      class="-ml-1.5 inline-flex items-center gap-1.5 rounded-md px-1.5 py-1 text-sm text-muted transition-colors duration-150 hover:bg-hover hover:text-ink"
    >
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M9.6 3.6 5.2 8l4.4 4.4"></path>
      </svg>
      {{ __('Templates') }}
    </a>

    <x-page-header :title="$viewModel->title()" :description="$viewModel->description()" />
  </div>

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
    class="space-y-8.5"
  >
    <x-form id="template-form" :method="$viewModel->method()" :action="$viewModel->action()" class="space-y-8.5">
      <x-box :title="__('Name')">
        <div class="{{ $panel }}">
          <div class="space-y-2.5 text-sm leading-relaxed text-body">
            <p>{{ __('The name is what the picker shows when somebody makes a record.') }}</p>

            <p>{{ __('The slug is what the URL and the API use. It has to be unique in your account.') }}</p>
          </div>

          <div class="space-y-3.5">
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
              <div class="space-y-1.5">
                <x-label for="slug" :value="__('Slug')" />

                <input
                  id="slug"
                  type="text"
                  value="{{ $values['slug'] }}"
                  readonly
                  disabled
                  aria-describedby="slug-help"
                  class="block w-full appearance-none rounded-md border border-hairline-strong bg-input px-3 py-2.5 font-mono text-base text-ink opacity-60 sm:text-sm"
                />

                <p id="slug-help" class="text-xs text-muted">{{ $viewModel->slugHelp() }}</p>
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
        </div>
      </x-box>

      @unless ($viewModel->isEdit())
        <x-box :title="__('What it describes')">
          <div class="{{ $panel }}">
            <div class="space-y-2.5 text-sm leading-relaxed text-body">
              <p>{{ __('A template describes one kind of record. This is settled now and cannot be changed later, since every record made with it would be left rendering something else.') }}</p>

              @unless ($viewModel->hasEntityTypes())
                <p class="text-muted">{{ __('You have not invented any kinds of record yet, so a template can only describe a contact for now.') }}</p>
              @endunless
            </div>

            <div class="space-y-3.5">
              <fieldset class="space-y-2">
                <legend class="sr-only">{{ __('What it describes') }}</legend>

                <label class="flex cursor-pointer items-start gap-2.5 rounded-lg border border-hairline-soft bg-sunken px-3 py-2.5">
                  <input type="radio" name="applies_to" value="contact" x-model="appliesTo" class="mt-0.5" />

                  <span class="space-y-0.5">
                    <span class="block text-sm font-semibold text-ink">{{ __('A contact') }}</span>
                    <span class="block text-sm text-body">{{ __('The people every vault keeps.') }}</span>
                  </span>
                </label>

                @if ($viewModel->hasEntityTypes())
                  <label class="flex cursor-pointer items-start gap-2.5 rounded-lg border border-hairline-soft bg-sunken px-3 py-2.5">
                    <input type="radio" name="applies_to" value="entity" x-model="appliesTo" class="mt-0.5" />

                    <span class="space-y-0.5">
                      <span class="block text-sm font-semibold text-ink">{{ __('A kind of record you invented') }}</span>
                      <span class="block text-sm text-body">{{ __('Animals, companies, wine bottles, whatever you named.') }}</span>
                    </span>
                  </label>
                @endif

                <x-error :messages="$errors->get('applies_to')" />
              </fieldset>

              @if ($viewModel->hasEntityTypes())
                <div x-cloak x-show="appliesTo === 'entity'" class="space-y-1.5">
                  <x-label for="entity_type_id" :value="__('Which kind')" />

                  <select
                    id="entity_type_id"
                    name="entity_type_id"
                    class="block w-full appearance-none rounded-md border border-hairline-strong bg-input px-3 py-2.5 text-base text-ink sm:text-sm"
                  >
                    <option value="">{{ __('Pick one') }}</option>

                    @foreach ($viewModel->entityTypes() as $type)
                      <option value="{{ $type['id'] }}" @selected($values['entityTypeId'] === (string) $type['id'])>{{ $type['name'] }}</option>
                    @endforeach
                  </select>

                  <x-error :messages="$errors->get('entity_type_id')" />
                </div>
              @endif
            </div>
          </div>
        </x-box>
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
            <button
              type="button"
              @click="open = true"
              class="cursor-pointer rounded-md border border-hairline-strong bg-canvas px-3.5 py-2.5 text-sm font-semibold text-error transition-colors duration-150 hover:bg-hover"
            >{{ __('Delete the template') }}</button>
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
</x-app-layout>
