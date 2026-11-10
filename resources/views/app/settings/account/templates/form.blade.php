{{-- The form that names a template, and the same form that renames one. --}}
{{-- @var \App\ViewModels\Settings\Account\Templates\TemplateFormViewModel $viewModel --}}
@php
  $values = $viewModel->values();
@endphp
<x-settings.page
  :title="$viewModel->title()"
  :user="$viewModel->user()"
  current="templates"
  :back-href="route('settings.templates.index')"
  :back-label="__('Back to the templates')"
>
  <x-page-header :title="$viewModel->title()" :description="$viewModel->description()" />

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
      <x-page-section :title="__('Name')" :description="__('The name is what the picker shows when somebody makes a record. The slug is what the URL and the API use.')">
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
              :placeholder="__('Animals, with the health and care blocks a vet visit needs.')"
              :help="__('Shown under the name on the list of templates.')"
              :error="$errors->get('description')"
            />
          </div>
        </x-panel>
      </x-page-section>

      @unless ($viewModel->isEdit())
        <x-page-section :title="__('What it describes')" :description="__('Fixed now and not changeable later. Every record made with it would otherwise show something else.')">
          <x-slot:help>
            <x-help
              :label="__('Help about what a template describes')"
              :title="__('What does this choice decide?')"
              guide="templates.change#what-a-template-describes"
            >{{ __('Where the template is offered. A template for contacts appears only when somebody adds a contact, and one for animals only when somebody adds an animal. If you pick the wrong one, create another template and delete this one while nothing uses it.') }}</x-help>
          </x-slot:help>

          <x-panel :flush="true">
            <fieldset>
              <legend class="sr-only">{{ __('What it describes') }}</legend>

              <label class="flex w-full items-start gap-3 border-b border-hairline px-4 py-3.5 text-left transition-colors duration-(--duration-fast) ease-out last:border-b-0 cursor-pointer hover:bg-hover focus-ring-inset">
                <input type="radio" name="applies_to" value="contact" x-model="appliesTo" class="mt-1" />

                <span class="min-w-0 flex-1">
                  <span class="block text-base font-semibold text-ink">{{ __('A contact') }}</span>
                  <span class="mt-px block text-sm text-pretty text-muted">{{ __('The people every vault keeps.') }}</span>
                </span>
              </label>

              @if ($viewModel->hasEntityTypes())
                <label class="flex w-full items-start gap-3 border-b border-hairline px-4 py-3.5 text-left transition-colors duration-(--duration-fast) ease-out last:border-b-0 cursor-pointer hover:bg-hover focus-ring-inset">
                  <input type="radio" name="applies_to" value="entity" x-model="appliesTo" class="mt-1" />

                  <span class="min-w-0 flex-1">
                    <span class="block text-base font-semibold text-ink">{{ __('A kind of record you created') }}</span>
                    <span class="mt-px block text-sm text-pretty text-muted">{{ __('Animals, companies, wine bottles, whatever you named.') }}</span>
                  </span>
                </label>
              @endif
            </fieldset>

            @if ($viewModel->hasEntityTypes())
              <div x-cloak x-show="appliesTo === 'entity'" class="border-t border-hairline px-4 py-4">
                <x-select
                  id="entity_type_id"
                  :label="__('Which kind')"
                  :error="$errors->get('entity_type_id')"
                >
                  <option value="">{{ __('Pick one') }}</option>

                  @foreach ($viewModel->entityTypes() as $type)
                    <option value="{{ $type['id'] }}" @selected($values['entityTypeId'] === (string) $type['id'])>{{ $type['name'] }}</option>
                  @endforeach
                </x-select>
              </div>
            @else
              <div class="border-t border-hairline px-4 py-4">
                <x-notice>{{ __('You have not created any kinds of record yet, so a template can only describe a contact for now.') }}</x-notice>
              </div>
            @endif

            <x-slot:footer>
              <x-error :messages="$errors->get('applies_to')" />
            </x-slot:footer>
          </x-panel>
        </x-page-section>
      @endunless
    </x-form>

    <div class="flex flex-wrap items-center gap-3.5">
      <div class="flex flex-wrap items-center gap-3 max-sm:w-full sm:ml-auto">
        <x-button.secondary :href="$viewModel->cancelUrl()" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

        <x-button form="template-form" ::disabled="! named">{{ $viewModel->saveLabel() }}</x-button>
      </div>
    </div>

    @if ($viewModel->isEdit())
      <x-delete-section :title="__('Delete the template')">
        <x-slot:trigger>
          <x-modal.warning
            :title="__('Delete “:name”?', ['name' => $values['name']])"
            :confirm="__('Delete the template')"
            :action="route('settings.templates.destroy', $viewModel->template())"
          >
            <x-slot:trigger>
              <x-button.danger type="button" @click="open = true">
                {{ __('Delete the template') }}
              </x-button.danger>
            </x-slot:trigger>

            <p>{{ __('Every version of it goes, and the slug stays taken so an old link cannot reach something else.') }}</p>

            <p>{{ __('This is refused while any record still points at one of its versions.') }}</p>
          </x-modal.warning>
        </x-slot:trigger>
      </x-delete-section>
    @endif
  </div>
</x-settings.page>
