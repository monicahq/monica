{{-- The form that adds a kind of link between two people, and the same form that rewords one. --}}
{{-- @var \App\ViewModels\Settings\Account\RelationshipTypes\RelationshipTypeFormViewModel $viewModel --}}
@php
  $values = $viewModel->values();
@endphp
<x-settings.page
  :title="$viewModel->title()"
  :user="$viewModel->user()"
  current="relationship-types"
  :back-href="route('settings.relationshipTypes.index')"
  :back-label="__('Back to relationship types')"
>
  <header class="flex flex-col gap-2">
    <div class="flex flex-wrap items-center gap-3">
      <h1 class="text-page-title text-ink">{{ $viewModel->title() }}</h1>

      @if ($viewModel->isSystem())
        <x-badge tone="info">{{ __('Supplied') }}</x-badge>
      @endif
    </div>

    <p class="max-w-[56ch] text-base text-pretty text-body">{{ $viewModel->description() }}</p>
  </header>

  <div
    x-data="{
      symmetric: @js($values['isSymmetric']),
      roleA: @js($values['roleA']),
      roleB: @js($values['roleB']),
      sentence(subject, other, role) {
        return @js($viewModel->sentenceTemplate())
          .replace(':one', subject)
          .replace(':two', other)
          .replace(':role', role.trim())
      },
      get otherRole() { return this.symmetric ? this.roleA : this.roleB },
      get written() { return this.roleA.trim() !== '' && this.otherRole.trim() !== '' },
      get previewOne() { return this.written ? this.sentence(@js($viewModel->exampleOne()), @js($viewModel->exampleTwo()), this.roleA) : '' },
      get previewTwo() { return this.written ? this.sentence(@js($viewModel->exampleTwo()), @js($viewModel->exampleOne()), this.otherRole) : '' },
    }"
    class="flex flex-col gap-8"
  >
    <x-form id="relationship-type-form" :method="$viewModel->method()" :action="$viewModel->action()" class="flex flex-col gap-8">
      <x-page-section :title="__('Name and category')">
        <x-panel>
          <div class="flex flex-col gap-4.5">
            <x-input
              id="name"
              :value="$values['name']"
              :label="__('Name')"
              :placeholder="__('Godparent / Godchild')"
              :help="__('Shown in settings and when you pick a relationship.')"
              :error="$errors->get('name')"
              maxlength="255"
              required
            />

            <x-select
              id="relationship_category_id"
              :label="__('Category')"
              :help="__('The category decides where the type is listed. It gives it no other behaviour.')"
              :error="$errors->get('relationship_category_id')"
              required
            >
              @foreach ($viewModel->categoryOptions() as $option)
                <option value="{{ $option['value'] }}" @selected($option['selected'])>{{ $option['label'] }}</option>
              @endforeach
            </x-select>
          </div>
        </x-panel>
      </x-page-section>

      <x-page-section :title="__('How it reads')">
        <x-panel>
          <div class="flex flex-col gap-4.5">
            @if ($viewModel->directionIsEditable())
              <fieldset class="flex flex-col gap-2">
                <legend class="sr-only">{{ __('How it reads') }}</legend>

                <x-option-row
                  type="radio"
                  id="direction_symmetric"
                  name="direction"
                  value="symmetric"
                  :checked="$values['isSymmetric']"
                  @change="symmetric = true"
                  :hint="__('Both people are described with the same word, as with Sibling or Friend.')"
                >{{ __('The same from both sides') }}</x-option-row>

                <x-option-row
                  type="radio"
                  id="direction_directed"
                  name="direction"
                  value="directed"
                  :checked="! $values['isSymmetric']"
                  @change="symmetric = false"
                  :hint="__('Each person is described with a different word, as with Parent and Child.')"
                >{{ __('Different on each side') }}</x-option-row>

                <x-error :id="$errors->has('direction') ? 'direction-error' : null" :messages="$errors->get('direction')" />
              </fieldset>
            @else
              <input type="hidden" name="direction" value="{{ $values['isSymmetric'] ? 'symmetric' : 'directed' }}" />

              <div class="flex flex-col gap-1.5">
                <div class="flex items-center gap-2.5 rounded-md border border-hairline bg-sunken px-3 py-2.5">
                  <x-icon name="lock" :size="15" :stroke-width="1.5" class="shrink-0 text-muted" />

                  <span class="text-sm text-ink">{{ $viewModel->lockedDirectionLabel() }}</span>
                </div>

                <p class="text-xs text-pretty text-muted">{{ $viewModel->lockedDirectionReason() }}</p>
              </div>
            @endif

            <div x-show="symmetric" x-cloak>
              <x-input
                id="role_a"
                x-model="roleA"
                ::disabled="! symmetric"
                :value="$values['roleA']"
                :label="__('Role, on both sides')"
                :placeholder="__('Sibling')"
                :help="__('A noun, without a name: sibling, cousin, friend.')"
                :error="$errors->get('role_a')"
                maxlength="255"
              />
            </div>

            <div x-show="! symmetric" x-cloak class="flex flex-col gap-4.5">
              <div class="grid gap-4 sm:grid-cols-2">
                <x-input
                  id="role_a_directed"
                  name="role_a"
                  x-model="roleA"
                  ::disabled="symmetric"
                  :value="$values['roleA']"
                  :label="__('First role')"
                  :placeholder="__('Parent')"
                  :error="$errors->get('role_a')"
                  maxlength="255"
                />

                <x-input
                  id="role_b"
                  x-model="roleB"
                  ::disabled="symmetric"
                  :value="$values['roleB']"
                  :label="__('Opposite role')"
                  :placeholder="__('Child')"
                  :error="$errors->get('role_b')"
                  maxlength="255"
                />
              </div>

              <p class="text-xs text-pretty text-muted">{{ __('Renaming a role never swaps the two people. The first role always stays on the first person.') }}</p>
            </div>

            <div
              aria-live="polite"
              class="flex flex-col gap-1.5 rounded-md border border-hairline bg-sunken px-3.5 py-3"
            >
              <p class="text-base text-ink" x-text="previewOne || @js(__('Write the words above to see how this reads.'))"></p>

              <p class="text-base text-ink" x-show="previewTwo" x-cloak x-text="previewTwo"></p>
            </div>
          </div>
        </x-panel>
      </x-page-section>

      <x-page-section :title="__('Availability')">
        <x-panel>
          <div class="flex flex-col gap-2">
            <x-checkbox id="is_active" :checked="$values['isActive']">
              {{ __('Offer this type for new relationships') }}
            </x-checkbox>

            <p class="text-xs text-pretty text-muted">{{ $viewModel->availabilityHelp() }}</p>
          </div>
        </x-panel>
      </x-page-section>
    </x-form>

    @if ($viewModel->behaviourTitle())
      <x-page-section :title="__('What the application does with it')">
        <x-panel :title="$viewModel->behaviourTitle()" icon="family">
          <p class="text-sm text-pretty text-body">{{ $viewModel->behaviourBody() }}</p>
        </x-panel>
      </x-page-section>
    @endif

    <div class="flex flex-wrap items-center gap-3.5">
      @if ($viewModel->isEdit())
        @include('app.settings.account.relationship-types._delete', ['viewModel' => $viewModel])
      @endif

      <div class="flex flex-wrap items-center gap-3 max-sm:w-full sm:ml-auto">
        <x-button.secondary :href="route('settings.relationshipTypes.index')" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

        <x-button form="relationship-type-form">{{ $viewModel->saveLabel() }}</x-button>
      </div>
    </div>
  </div>
</x-settings.page>
