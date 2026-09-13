{{-- Records that the person on screen is connected to somebody else, asking for one thing at a time. --}}
{{-- @var \App\ViewModels\Vaults\RelationshipFormViewModel $viewModel --}}
@php
  $values = $viewModel->values();
  $contact = $viewModel->contact();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$viewModel->vault()" :items="[['label' => __('People'), 'url' => $viewModel->peopleUrl()],
        ['label' => $contact->name, 'url' => $viewModel->contactUrl()],
        ['label' => $viewModel->title()],]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$viewModel->vault()" current="contacts" />
  </x-slot:sidebar>

  <div class="flex justify-center">
    <div
      x-data="relationshipForm(@js($viewModel->projection()))"
      class="w-full max-w-140 overflow-hidden rounded-lg border border-hairline bg-canvas shadow-pop"
    >
      <div class="flex items-start gap-3 border-b border-hairline p-4">
        <span class="flex size-7 flex-none items-center justify-center rounded-md border border-hairline bg-card text-body">
          <x-icon name="relationship" :size="16" />
        </span>

        <div class="min-w-0 flex-1">
          <h1 class="text-card-title text-ink">{{ $viewModel->title() }}</h1>

          <p class="mt-0.5 text-xs text-pretty text-muted">{{ $viewModel->description() }}</p>
        </div>
      </div>

      @if (! $viewModel->hasTypes())
        <div class="p-4">
          <x-notice tone="warning">{{ $viewModel->noTypesLine() }}</x-notice>
        </div>
      @else
        <x-form id="relationship-form" :method="$viewModel->method()" :action="$viewModel->action()">
          <input type="hidden" name="swapped" ::value="swapped ? 1 : 0" />

          <div class="p-4">
            <x-select
              id="relationship_type_id"
              x-model="typeId"
              x-on:change="chooseType()"
              :label="__('Relationship type')"
              :help="$viewModel->typeHelp()"
              :error="$errors->get('relationship_type_id')"
              required
            >
              <option value="">{{ __('Choose a type') }}</option>
              @foreach ($viewModel->typeGroups() as $group)
                <optgroup label="{{ $group['label'] }}">
                  @foreach ($group['types'] as $type)
                    <option value="{{ $type['value'] }}" @selected($values['typeId'] === (string) $type['value'])>{{ $type['label'] }}</option>
                  @endforeach
                </optgroup>
              @endforeach
            </x-select>
          </div>

          <div x-show="hasType" x-cloak class="flex flex-col gap-4 border-t border-hairline p-4">
            <div :class="swapped ? 'order-3' : 'order-1'">
              <x-caption tone="ink" class="mb-2 block" x-text="roleA.toUpperCase()"></x-caption>

              <div class="flex items-center gap-2.5">
                <x-contact-avatar :name="$contact->name" :size="30" />

                <span class="text-base font-semibold text-ink">{{ $contact->name }}</span>
              </div>
            </div>

            <div class="order-2 flex items-center justify-center">
              <x-button.secondary type="button" size="small" x-on:click="swap()" ::disabled="! hasType || symmetric">
                <x-slot:icon><x-icon name="relationship" :size="15" /></x-slot:icon>
                {{ __('Swap the two roles') }}
              </x-button.secondary>
            </div>

            <div :class="swapped ? 'order-1' : 'order-3'">
              <x-caption tone="ink" class="mb-2 block" x-text="roleB.toUpperCase()"></x-caption>

              <fieldset class="flex flex-col gap-1.5">
                <legend class="sr-only">{{ __('Who holds this role') }}</legend>

                <x-option-row
                  id="mode_unknown"
                  name="mode"
                  type="radio"
                  value="unknown"
                  :checked="$values['mode'] === 'unknown'"
                  x-model="mode"
                  :hint="__('Records the role and nothing else')"
                >{{ __('I do not know their name') }}</x-option-row>

                <x-option-row
                  id="mode_known"
                  name="mode"
                  type="radio"
                  value="known"
                  :checked="$values['mode'] === 'known'"
                  x-model="mode"
                  :hint="__('A first name on its own is enough')"
                >{{ __('I know their name') }}</x-option-row>

                <x-option-row
                  id="mode_existing"
                  name="mode"
                  type="radio"
                  value="existing"
                  :checked="$values['mode'] === 'existing'"
                  x-model="mode"
                  :hint="__('Links two people you already keep')"
                >{{ __('Choose someone already written down') }}</x-option-row>

                <x-error :messages="$errors->get('mode')" />
              </fieldset>
            </div>

            <div x-show="mode === 'known'" x-cloak class="order-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
              <x-input
                id="first_name"
                x-model="first"
                ::disabled="mode !== 'known'"
                :value="$values['firstName']"
                :label="__('First name')"
                placeholder="Théo"
                :error="$errors->get('first_name')"
                maxlength="255"
              />

              <x-input
                id="last_name"
                x-model="last"
                ::disabled="mode !== 'known'"
                :value="$values['lastName']"
                :label="__('Last name')"
                optional
                placeholder="Laurent"
                :error="$errors->get('last_name')"
                maxlength="255"
              />
            </div>

            <div x-show="mode === 'existing'" x-cloak class="order-5 flex flex-col gap-2">
              <input type="hidden" name="other_contact_id" ::value="picked" ::disabled="mode !== 'existing'" />

              @include('app.vaults.contacts.relationships._candidates', ['viewModel' => $viewModel])

              <x-error :messages="$errors->get('other_contact_id')" />
            </div>

            <div x-show="complete" x-cloak class="order-6">
              <x-notice tone="quiet" icon="info">
                <div aria-live="polite" class="flex min-w-0 flex-col gap-0.5">
                  <p class="text-sm font-semibold text-pretty text-ink" x-text="previewOne"></p>
                  <p class="text-xs text-pretty text-muted" x-text="previewTwo"></p>
                </div>
              </x-notice>
            </div>
          </div>

          <div x-show="hasType && asksForAContact" x-cloak class="border-t border-hairline px-4 py-3.5">
            <x-checkbox id="creates_record" :checked="$values['createsRecord']">
              <span class="block text-sm text-ink">{{ __('Create a contact record for this person') }}</span>
              <span class="mt-0.5 block text-xs text-pretty text-muted">{{ __('Leave it off and they stay out of your list of people, with a page of their own all the same.') }}</span>
            </x-checkbox>
          </div>
        </x-form>

        <div class="flex items-center gap-3 border-t border-hairline bg-card px-4 py-3">
          <x-button.ghost size="small" :href="$viewModel->contactUrl()" data-turbo="true">{{ __('Cancel') }}</x-button.ghost>

          <span class="flex-1 text-right text-xs text-pretty text-muted" x-text="hint"></span>

          <x-button form="relationship-form" size="small" ::disabled="! complete">{{ __('Add relationship') }}</x-button>
        </div>
      @endif
    </div>
  </div>
</x-shell>
