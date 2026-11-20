{{-- The form that names a copy of a template, and its slug, before the copy is made. --}}
{{-- @var \App\ViewModels\Settings\Account\Templates\TemplateCopyViewModel $viewModel --}}
@php
  $values = $viewModel->values();
@endphp
<x-settings.page
  :title="__('Copy :name', ['name' => $viewModel->template()->name])"
  :user="$viewModel->user()"
  current="templates"
  :back-href="$viewModel->cancelUrl()"
  :back-label="__('Back to the templates')"
>
  <x-page-header
    :title="__('Copy :name', ['name' => $viewModel->template()->name])"
    :description="__('The copy is a separate template. Change it as you like: the original and every record using it stay as they are.')"
  />

  <div
    x-data="{
      name: @js($values['name']),
      slug: @js($values['slug']),
      slugTouched: @js($values['slugTouched']),
      get named() { return this.name.trim() !== '' },
    }"
    class="flex flex-col gap-8"
  >
    <x-form id="template-copy-form" method="post" :action="$viewModel->action()" class="flex flex-col gap-8">
      <x-page-section :title="__('Name')" :description="__('The name is what the picker shows when somebody makes a record. The slug is what the URL and the API use.')">
        <x-panel>
          <div class="flex flex-col gap-4.5">
            <x-input
              id="name"
              x-model="name"
              @input="if (! slugTouched) slug = window.slugify(name)"
              :value="$values['name']"
              :label="__('Name')"
              :error="$errors->get('name')"
              maxlength="255"
              required
            />

            <x-input
              id="slug"
              x-model="slug"
              @input="slugTouched = $event.target.value.trim() !== ''"
              @blur="slug = window.slugify(slug)"
              :value="$values['slug']"
              :label="__('Slug')"
              :help="__('Lowercase letters, numbers and dashes. It is the address of the copy and cannot be changed once the copy exists. Left empty, it is made from the name.')"
              :error="$errors->get('slug')"
              :maxlength="\App\Models\Template::SLUG_LENGTH"
              class="font-mono"
            />
          </div>
        </x-panel>
      </x-page-section>
    </x-form>

    <div class="flex flex-wrap items-center gap-3.5">
      <div class="flex flex-wrap items-center gap-3 max-sm:w-full sm:ml-auto">
        <x-button.secondary :href="$viewModel->cancelUrl()" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

        <x-button form="template-copy-form" ::disabled="! named">{{ __('Make the copy') }}</x-button>
      </div>
    </div>
  </div>
</x-settings.page>
