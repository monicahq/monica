{{-- The form that puts somebody in a group, or changes their role and when they joined and left. --}}
{{-- @var \App\ViewModels\Vaults\GroupMemberFormViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $values = $viewModel->values();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="[['label' => __('Groups'), 'url' => route('groups.index', $vault)], ['label' => $viewModel->groupName(), 'url' => $viewModel->groupUrl()], ['label' => $viewModel->title()]]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="groups" />
  </x-slot:sidebar>

  <x-page-header :title="$viewModel->title()" class="max-w-140" />

  <x-form id="group-member-form" :method="$viewModel->isEditing() ? 'put' : 'post'" :action="$viewModel->action()" class="max-w-140 space-y-8.5">
    <x-box :title="__('Who')">
      <div class="space-y-4">
        @unless ($viewModel->isEditing())
          <x-record-picker
            id="contact"
            name="contact"
            :url="$viewModel->candidatesUrl()"
            :label="__('Person')"
            :chosen="old('contact')"
            :error="$errors->get('contact')"
          />
        @endunless

        <x-input
          id="role"
          :value="$values['role']"
          :label="__('Role')"
          optional
          :placeholder="__('Father, captain, best man')"
          :error="$errors->get('role')"
          maxlength="255"
        />
      </div>
    </x-box>

    <x-box :title="__('When')" :description="__('Leave a date empty when you do not know it. Giving a date they left is how somebody leaves the group; if they come back, add them again.')">
      <div class="grid gap-4 sm:grid-cols-2">
        <x-input id="joined_on" type="date" :label="__('Joined on')" optional :value="$values['joinedOn']" :error="$errors->get('joined_on')" />

        <x-select id="joined_precision" :label="__('How precisely')" :error="$errors->get('joined_precision')">
          @foreach ($viewModel->precisions() as $value => $label)
            <option value="{{ $value }}" @selected($values['joinedPrecision'] === $value)>{{ $label }}</option>
          @endforeach
        </x-select>

        <x-input id="left_on" type="date" :label="__('Left on')" optional :value="$values['leftOn']" :error="$errors->get('left_on')" />

        <x-select id="left_precision" :label="__('How precisely')" :error="$errors->get('left_precision')">
          @foreach ($viewModel->precisions() as $value => $label)
            <option value="{{ $value }}" @selected($values['leftPrecision'] === $value)>{{ $label }}</option>
          @endforeach
        </x-select>
      </div>
    </x-box>

    <div class="flex flex-wrap items-center justify-end gap-2.5 border-t border-hairline pt-5">
      <x-button.secondary :href="$viewModel->groupUrl()" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>
      <x-button>{{ $viewModel->isEditing() ? __('Save the membership') : __('Add to the group') }}</x-button>
    </div>
  </x-form>

  @if ($viewModel->destroyAction() !== null)
    <x-delete-section :title="__('Delete this membership')" icon="people" class="max-w-140">
      <p>{{ __('Only for a membership recorded by mistake. Somebody who left keeps their membership with the date they left, so the group keeps its history.') }}</p>

      <x-slot:trigger>
        <x-modal.warning
          :title="__('Delete this membership?')"
          :confirm="__('Delete the membership')"
          :cancel="__('Keep it')"
          :action="$viewModel->destroyAction()"
        >
          <x-slot:trigger>
            <x-button.danger type="button" @click="open = true">{{ __('Delete this membership') }}</x-button.danger>
          </x-slot:trigger>

          <p>{{ __('This cannot be undone.') }}</p>
        </x-modal.warning>
      </x-slot:trigger>
    </x-delete-section>
  @endif
</x-shell>
