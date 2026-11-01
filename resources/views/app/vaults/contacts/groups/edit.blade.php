{{-- The groups somebody is in, ticked, with a way to start a new one with them in it. --}}
{{-- @var \App\ViewModels\Vaults\ContactGroupsFormViewModel $viewModel --}}
@php
  $contact = $viewModel->contact();
  $groups = $viewModel->groups();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$viewModel->vault()" :items="[['label' => __('People'), 'url' => $viewModel->peopleUrl()],
        ['label' => $contact->name, 'url' => $viewModel->contactUrl()],
        ['label' => __('Groups')],]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$viewModel->vault()" current="contacts" />
  </x-slot:sidebar>

  <x-page-header :title="$viewModel->title()" :description="__('Ticking a group adds :name to it today. Unticking one records that they left it today.', ['name' => $contact->name])" class="max-w-140">
    <x-slot:actions>
      <x-button.secondary :href="$viewModel->newGroupUrl()" data-turbo="true" size="small">
        <x-slot:icon>
          <x-icon name="plus" :size="16" :stroke-width="1.6" />
        </x-slot:icon>

        {{ __('Create a group') }}
      </x-button.secondary>
    </x-slot:actions>
  </x-page-header>

  <x-form id="contact-groups-form" method="put" :action="$viewModel->action()" class="max-w-140 space-y-8.5">
    <x-box :title="__('Groups')">
      @if ($groups === [])
        <p class="text-sm text-pretty text-muted">{{ __('This vault has no group yet. Create one and :name will be its first member.', ['name' => $contact->name]) }}</p>
      @else
        <fieldset class="space-y-3" @if ($errors->has('groups')) aria-describedby="groups-error" @endif>
          <legend class="sr-only">{{ __('Groups') }}</legend>

          @foreach ($groups as $group)
            <x-checkbox id="group-{{ $group['uuid'] }}" name="groups[]" :value="$group['uuid']" :checked="$group['isChosen']">
              {{ $group['name'] }}
            </x-checkbox>
          @endforeach
        </fieldset>

        @error('groups')
          <p id="groups-error" class="mt-2 text-sm text-error">{{ $message }}</p>
        @enderror
      @endif
    </x-box>

    <div class="flex flex-wrap items-center justify-end gap-2.5 border-t border-hairline pt-5">
      <x-button.secondary :href="$viewModel->contactUrl()" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

      @if ($groups !== [])
        <x-button>{{ __('Save the groups') }}</x-button>
      @endif
    </div>
  </x-form>
</x-shell>
