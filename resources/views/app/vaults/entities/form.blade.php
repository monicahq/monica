{{-- The form that writes down a record of an entity type, drawn from the template the vault uses. --}}
{{-- @var \App\ViewModels\Vaults\EntityFormViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="[['label' => $viewModel->listName(), 'url' => $viewModel->listUrl()],
        ['label' => $viewModel->title()],]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" :current="$viewModel->current()" />
  </x-slot:sidebar>

  <x-page-header :title="$viewModel->title()" />

  <div class="flex flex-col gap-8">
    <x-form id="entity-form" method="post" :action="$viewModel->action()" :upload="$viewModel->holdsAFile()" class="flex flex-col gap-8">
      <input type="hidden" name="structure_version_id" value="{{ $viewModel->structureVersionId() }}" />

      @if ($errors->has('fields'))
        <x-notice tone="danger">{{ $errors->first('fields') }}</x-notice>
      @endif

      <x-page-section :title="__('Name')">
        <x-panel>
          <x-input
            id="name"
            :value="$viewModel->name()"
            :label="__('Name')"
            :error="$errors->get('name')"
            maxlength="255"
            autofocus
            required
            required-mark
            class="max-w-105"
          />
        </x-panel>
      </x-page-section>

      @foreach ($viewModel->pages() as $page)
        <x-page-section :title="$page['name']">
          @foreach ($page['sections'] as $section)
            <x-panel :title="$section['name']">
              <div class="flex flex-col gap-5">
                @foreach ($section['fields'] as $field)
                  <x-field-input
                    :field="$field['field']"
                    :value="$field['rawValue']"
                    :reminder="null"
                    :messages="$errors"
                    :error="$errors->get('fields.'.$field['id'])"
                    :vault="$viewModel->vault()"
                  />
                @endforeach
              </div>
            </x-panel>
          @endforeach
        </x-page-section>
      @endforeach
    </x-form>

    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-hairline pt-4.5">
      <x-button.secondary :href="$viewModel->listUrl()" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

      <x-button form="entity-form">{{ __('Save') }}</x-button>
    </div>
  </div>
</x-shell>
