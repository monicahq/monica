{{-- Corrects a link already written down: what it says, whether it ended, and who the other side is. --}}
{{-- @var \App\ViewModels\Vaults\RelationshipEditViewModel $viewModel --}}
@php
  $values = $viewModel->values();
  $contact = $viewModel->contact();
  $other = $viewModel->otherSide();
  $otherState = $viewModel->otherState();
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

  <div x-data="{ role: @js($viewModel->otherRole()), mode: @js($values['mode']), ended: @js($values['hasEnded']) }">
    <x-form :method="$viewModel->method()" :action="$viewModel->action()">
      <x-panel flush>
        <div class="p-4">
          <x-select
            id="relationship"
            :label="__('Relationship')"
            :error="$errors->get('relationship')"
            x-on:change="role = $event.target.selectedOptions[0].dataset.role"
            autocomplete="off"
            required
          >
            @foreach ($viewModel->sentenceGroups() as $group)
              <optgroup label="{{ $group['label'] }}">
                @foreach ($group['options'] as $option)
                  <option value="{{ $option['value'] }}" data-role="{{ $option['otherRole'] }}" @selected($values['relationship'] === $option['value'])>{{ $option['label'] }}</option>
                @endforeach
              </optgroup>
            @endforeach
          </x-select>
        </div>

        @if ($otherState === \App\ViewModels\Vaults\RelationshipEditViewModel::OTHER_IS_NAMELESS)
          <fieldset class="border-t border-hairline p-4">
            <legend class="float-left mb-2 w-full px-0"><x-caption x-text="role">{{ $viewModel->otherRole() }}</x-caption></legend>

            <div class="clear-left flex flex-col gap-1.5">
              <x-option-row id="mode_unknown" name="mode" type="radio" value="unknown" :checked="$values['mode'] === 'unknown'" x-model="mode">{{ __('I do not know their name') }}</x-option-row>

              <x-option-row id="mode_known" name="mode" type="radio" value="known" :checked="$values['mode'] === 'known'" x-model="mode">{{ __('I know their name now') }}</x-option-row>
            </div>

            <fieldset x-show="mode === 'known'" x-cloak :disabled="mode !== 'known'" class="mt-4 grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2">
              <x-input id="first_name" :value="$values['firstName']" :label="__('First name')" :error="$errors->get('first_name')" maxlength="255" />

              <x-input id="last_name" :value="$values['lastName']" :label="__('Last name')" :error="$errors->get('last_name')" optional maxlength="255" />
            </fieldset>

            <div class="mt-4">
              <x-checkbox id="creates_record" :checked="$values['createsRecord']" :error="$errors->get('creates_record')">
                <span class="text-sm text-ink">{{ __('Create a contact record for this person') }}</span>
              </x-checkbox>
            </div>
          </fieldset>
        @else
          <div class="flex flex-col gap-3 border-t border-hairline p-4">
            <x-caption x-text="role">{{ $viewModel->otherRole() }}</x-caption>

            @if ($otherState === \App\ViewModels\Vaults\RelationshipEditViewModel::OTHER_HAS_A_RECORD)
              <div class="flex items-center gap-2.5">
                <x-contact-avatar :name="$other->name" :size="32" />

                <span class="text-base font-semibold text-ink">{{ $other->name }}</span>
              </div>
            @else
              <input type="hidden" name="mode" value="known" />

              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-input id="first_name" :value="$values['firstName']" :label="__('First name')" :help="$viewModel->renameLine()" :error="$errors->get('first_name')" required maxlength="255" />

                <x-input id="last_name" :value="$values['lastName']" :label="__('Last name')" :error="$errors->get('last_name')" optional maxlength="255" />
              </div>

              <x-checkbox id="creates_record" :checked="$values['createsRecord']" :error="$errors->get('creates_record')">
                <span class="text-sm text-ink">{{ __('Create a contact record for this person') }}</span>
              </x-checkbox>
            @endif
          </div>
        @endif

        <div class="flex flex-col gap-4 border-t border-hairline p-4">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-input
              type="date"
              id="started_on"
              :label="__('Started on')"
              :value="$values['startedOn']"
              :error="$errors->get('started_on')"
              optional
            />

            <fieldset x-show="ended" x-cloak :disabled="! ended" class="min-w-0">
              <x-input
                type="date"
                id="ended_on"
                :label="__('Ended on')"
                :value="$values['endedOn']"
                :error="$errors->get('ended_on')"
                optional
              />
            </fieldset>
          </div>

          <x-checkbox id="has_ended" :checked="$values['hasEnded']" x-model="ended">
            <span class="text-sm text-ink">{{ __('This relationship has ended') }}</span>
          </x-checkbox>
        </div>

        <x-slot:footer>
          <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-muted">{{ __('Saved on both records at once.') }}</p>

            <div class="flex items-center gap-3">
              <x-button.secondary :href="$viewModel->backUrl()" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

              <x-button type="submit">{{ __('Save changes') }}</x-button>
            </div>
          </div>
        </x-slot:footer>
      </x-panel>
    </x-form>
  </div>
</x-shell>
