{{-- The form that creates a group or changes its name, its description and its dates. --}}
{{-- @var \App\ViewModels\Vaults\GroupFormViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $values = $viewModel->values();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="[['label' => __('Groups'), 'url' => route('groups.index', $vault)], ['label' => $viewModel->title()]]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="groups" />
  </x-slot:sidebar>

  <x-page-header :title="$viewModel->title()" class="max-w-140" />

  <x-form id="group-form" :method="$viewModel->isEditing() ? 'put' : 'post'" :action="$viewModel->action()" class="max-w-140 space-y-8.5">
    <x-box :title="__('What the group is')">
      <div class="space-y-4">
        <x-input
          id="name"
          :value="$values['name']"
          :label="__('Name')"
          :placeholder="__('Halpert household')"
          :error="$errors->get('name')"
          maxlength="255"
          autofocus
          required
        />

        <x-textarea
          id="description"
          :label="__('Description')"
          optional
          :value="$values['description']"
          :error="$errors->get('description')"
        />
      </div>
    </x-box>

    <x-box :title="__('When it existed')" :description="__('Leave a date empty when you do not know it. The end stays empty while the group exists.')">
      <div class="grid gap-4 sm:grid-cols-2">
        <x-input id="started_on" type="date" :label="__('Started on')" optional :value="$values['startedOn']" :error="$errors->get('started_on')" />

        <x-select id="started_precision" :label="__('How precisely')" :error="$errors->get('started_precision')">
          @foreach ($viewModel->precisions() as $value => $label)
            <option value="{{ $value }}" @selected($values['startedPrecision'] === $value)>{{ $label }}</option>
          @endforeach
        </x-select>

        <x-input id="ended_on" type="date" :label="__('Ended on')" optional :value="$values['endedOn']" :error="$errors->get('ended_on')" />

        <x-select id="ended_precision" :label="__('How precisely')" :error="$errors->get('ended_precision')">
          @foreach ($viewModel->precisions() as $value => $label)
            <option value="{{ $value }}" @selected($values['endedPrecision'] === $value)>{{ $label }}</option>
          @endforeach
        </x-select>
      </div>
    </x-box>

    <div class="flex flex-wrap items-center justify-end gap-2.5 border-t border-hairline pt-5">
      <x-button.secondary :href="$viewModel->cancelUrl()" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>
      <x-button>{{ $viewModel->isEditing() ? __('Save the group') : __('Create the group') }}</x-button>
    </div>
  </x-form>

  @if ($viewModel->destroyAction() !== null)
    <x-delete-section :title="__('Delete this group')" icon="people" class="max-w-140">
      <p>{{ __('The people in it stay as they are. Only the group and the memberships it holds go, and the group can be brought back right after.') }}</p>

      <x-slot:trigger>
        <x-modal.warning
          :title="__('Delete “:name”?', ['name' => $viewModel->groupName()])"
          :confirm="__('Delete the group')"
          :cancel="__('Keep it')"
          :action="$viewModel->destroyAction()"
        >
          <x-slot:trigger>
            <x-button.danger type="button" @click="open = true">
              {{ __('Delete this group') }}
            </x-button.danger>
          </x-slot:trigger>

          <p>{{ __('The people in it are not deleted.') }}</p>
        </x-modal.warning>
      </x-slot:trigger>
    </x-delete-section>
  @endif
</x-shell>
