{{-- Corrects a link already written down: what it is, which way it reads, whether it still runs. --}}
{{-- @var \App\ViewModels\Vaults\RelationshipEditViewModel $viewModel --}}
@php
  $values = $viewModel->values();
  $contact = $viewModel->contact();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$viewModel->vault()" :items="[['label' => __('People'), 'url' => route('contacts.index', ['vault' => $viewModel->vault()])],
        ['label' => $contact->name, 'url' => $viewModel->backUrl()],
        ['label' => $viewModel->title()],]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$viewModel->vault()" current="contacts" />
  </x-slot:sidebar>

  <x-page-header :title="$viewModel->title()" :description="$viewModel->description()" />

  <x-form :method="$viewModel->method()" :action="$viewModel->action()">
    <x-panel flush>
      <div class="p-4">
        <x-select
          id="relationship_type_id"
          :label="__('Relationship type')"
          :error="$errors->get('relationship_type_id')"
          required
        >
          @foreach ($viewModel->typeGroups() as $group)
            <optgroup label="{{ $group['label'] }}">
              @foreach ($group['types'] as $type)
                <option value="{{ $type['value'] }}" @selected($values['typeId'] === (string) $type['value'])>{{ $type['label'] }}</option>
              @endforeach
            </optgroup>
          @endforeach
        </x-select>
      </div>

      @unless ($viewModel->isSymmetric())
        <fieldset class="border-t border-hairline p-4">
          <legend class="float-left mb-2 w-full px-0"><x-caption>{{ __('Which way round') }}</x-caption></legend>

          @foreach ($viewModel->directions() as $direction)
            <x-option-row
              id="swapped-{{ $direction['value'] }}"
              name="swapped"
              type="radio"
              :value="$direction['value']"
              :checked="$values['swapped'] === $direction['value']"
            >{{ $direction['label'] }}</x-option-row>
          @endforeach
        </fieldset>
      @endunless

      <fieldset class="border-t border-hairline p-4">
        <legend class="float-left mb-2 w-full px-0"><x-caption>{{ __('Is it still going') }}</x-caption></legend>

        @foreach ($viewModel->statuses() as $status)
          <x-option-row
            id="status-{{ $status['value'] }}"
            name="status"
            type="radio"
            :value="$status['value']"
            :checked="$values['status'] === $status['value']"
          >{{ $status['label'] }}</x-option-row>
        @endforeach
      </fieldset>

      <div class="grid gap-4 border-t border-hairline p-4 sm:grid-cols-2">
        <x-input
          type="date"
          id="started_on"
          name="started_on"
          :label="__('Started on')"
          :value="$values['startedOn']"
          :error="$errors->get('started_on')"
          optional
        />

        <x-input
          type="date"
          id="ended_on"
          name="ended_on"
          :label="__('Ended on')"
          :value="$values['endedOn']"
          :error="$errors->get('ended_on')"
          optional
        />
      </div>

      <x-slot:footer>
        <div class="flex items-center justify-end gap-3">
          <x-button.secondary :href="$viewModel->backUrl()" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

          <x-button type="submit">{{ __('Save') }}</x-button>
        </div>
      </x-slot:footer>
    </x-panel>
  </x-form>
</x-shell>
