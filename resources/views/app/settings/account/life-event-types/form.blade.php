{{-- The form that adds a nature a life event can be recorded as, and the same form that changes one. --}}
{{-- @var \App\ViewModels\Settings\Account\LifeEventTypes\LifeEventTypeFormViewModel $viewModel --}}
@php
  $blocks = $viewModel->blocks();
  $families = $viewModel->fieldTypeFamilies();
  $marks = $viewModel->fieldTypeMarks();
  $settingsByType = $viewModel->settingsByFieldType();
  $choiceTypes = $viewModel->typesOfferingChoices();
  $editor = ['families' => $families, 'marks' => $marks, 'settingsByType' => $settingsByType, 'choiceTypes' => $choiceTypes];
  $blank = ['reference' => '__ref__', 'key' => null, 'group' => '', 'label' => '', 'type' => 'text', 'isRequired' => false, 'helpText' => '', 'settings' => [], 'choices' => []];
@endphp
<x-settings.page
  :title="$viewModel->title()"
  :user="$viewModel->user()"
  current="life-event-types"
  :back-href="$viewModel->backUrl()"
  :back-label="__('Back to life event types')"
>
  <x-slot:footer>
    <div class="flex w-full flex-wrap items-center justify-end gap-3">
      <x-button.secondary :href="$viewModel->backUrl()" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

      <x-button type="submit" form="life-event-type-form">{{ $viewModel->submitLabel() }}</x-button>
    </div>
  </x-slot:footer>

  <header class="flex flex-col gap-2">
    <div class="flex flex-wrap items-center gap-x-4 gap-y-3">
      <h1 class="text-page-title text-ink">{{ $viewModel->title() }}</h1>

      @if ($viewModel->isSystem())
        <x-badge tone="info">{{ __('Supplied') }}</x-badge>
      @endif

      {{-- Outside the form, so it names the form it belongs to, the way the footer's submit button does. --}}
      <div
        x-data="{ active: @js($viewModel->isActive()) }"
        class="flex shrink-0 items-center gap-2.5 sm:ml-auto"
      >
        <input type="hidden" name="is_active" value="0" form="life-event-type-form" />

        <label for="is_active" class="flex cursor-pointer items-center">
          <x-switch
            id="is_active"
            form="life-event-type-form"
            :checked="$viewModel->isActive()"
            :title="$viewModel->availabilityHelp()"
            aria-describedby="life-event-type-availability"
            x-model="active"
          />
        </label>

        <span
          class="text-sm font-medium text-ink"
          x-text="active ? @js(__('Offered when recording')) : @js(__('Not offered when recording'))"
        >{{ $viewModel->isActive() ? __('Offered when recording') : __('Not offered when recording') }}</span>
      </div>
    </div>

    <p class="max-w-reading text-base text-pretty text-body">{{ $viewModel->description() }}</p>

    <p id="life-event-type-availability" class="sr-only">{{ $viewModel->availabilityHelp() }}</p>
  </header>

  <div x-data="{ icon: @js($viewModel->icon()), colour: @js($viewModel->colour()), open: false, typed: @js($viewModel->name()) }">
  <x-form
    id="life-event-type-form"
    :method="$viewModel->method()"
    :action="$viewModel->action()"
    class="flex flex-col gap-8"
  >
    {{-- No panel here, as the design has none: x-panel is overflow-hidden and would clip the two pickers. --}}
    <x-page-section :title="__('Name and category')">
        <div class="flex flex-wrap items-start gap-x-6 gap-y-4">
          <x-mark-picker
            id="life-event-type-mark"
            layout="split"
            :glyphs="$viewModel->glyphs()"
            :colours="$viewModel->colours()"
            :icon="$viewModel->icon()"
            :colour="$viewModel->colour()"
          />

          <div class="flex min-w-65 flex-1 basis-80 flex-col gap-4.5">
          <div class="grid gap-4.5 sm:grid-cols-2">
            <x-input
              id="name"
              :value="$viewModel->name()"
              :label="__('Name')"
              :placeholder="__('Coffee')"
              :error="$errors->get('name')"
              maxlength="255"
              required
              x-model="typed"
            />

            <x-select
              id="life_event_category_id"
              :label="__('Category')"
              :error="$errors->get('life_event_category_id')"
              required
            >
              @foreach ($viewModel->categories() as $option)
                <option value="{{ $option['id'] }}" @selected($option['id'] === $viewModel->categoryId())>{{ $option['label'] }}</option>
              @endforeach
            </x-select>
          </div>

          <x-input
            id="description"
            :value="$viewModel->descriptionValue()"
            :label="__('Description')"
            :optional="true"
            :placeholder="__('A short coffee, in person')"
            :help="__('Shown under the name when two types could be confused.')"
            :error="$errors->get('description')"
            maxlength="255"
          />
          </div>
        </div>
    </x-page-section>

    {{-- The component sits on the section so that "Add a group", which the heading row holds, is inside the editor. --}}
    <x-page-section :title="__('Fields')" x-data="lifeEventTypeFields" data-field-editor>
      <x-slot:actions>
        <x-button.secondary type="button" size="small" @click="addBlock($el)">
          <x-icon name="layers" :size="15" :stroke-width="1.6" />

          {{ __('Add a group') }}
        </x-button.secondary>
      </x-slot:actions>

      <div class="flex flex-col gap-3">
        <x-error :messages="$errors->get('groups.*.name')" />
        <x-error :messages="$errors->get('fields.*.label')" />
        <x-error :messages="$errors->get('fields.*.type')" />

        <div data-blocks class="flex flex-col gap-3">
          @foreach ($blocks as $block)
            @include('app.settings.account.life-event-types._block', array_merge($editor, ['block' => $block]))
          @endforeach
        </div>

        <template data-block-template>
          @include('app.settings.account.life-event-types._block', array_merge($editor, [
            'block' => ['reference' => '__ref__', 'placementId' => null, 'name' => '', 'isGroup' => true, 'fields' => []],
          ]))
        </template>

        <template data-field-template>
          @include('app.settings.account.life-event-types._field', array_merge($editor, ['field' => $blank]))
        </template>

        <template data-choice-template>
          @include('app.settings.account.life-event-types._choice', ['reference' => '__ref__', 'choice' => ''])
        </template>
      </div>
    </x-page-section>

  </x-form>
  </div>

  @if ($viewModel->isEdit())
    <x-page-section :title="__('Removing this type')">
      @if ($viewModel->canDelete())
        <x-panel tone="danger" :title="__('Delete this type')" icon="close">
          <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
            <p class="min-w-60 flex-1 text-sm text-pretty text-body">{{ __('No life event is recorded with this type, so it can be deleted for good.') }}</p>

            <x-delete-action
              :title="__('Delete “:name”?', ['name' => $viewModel->title()])"
              :confirm="__('Delete the type')"
              :action="$viewModel->deleteAction()"
              :label="__('Delete this type')"
              variant="danger"
            >
              <p>{{ __('No life event is recorded with this type, so nothing you have written will change.') }}</p>
            </x-delete-action>
          </div>
        </x-panel>
      @else
        {{-- The control stays and says why it refuses, which .claude/rules/policies.md asks for. --}}
        <x-panel>
          <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
            <p id="life-event-type-delete-reason" class="min-w-60 flex-1 text-sm text-pretty text-body">{{ $viewModel->deleteRefusal() }}</p>

            <x-button.secondary type="button" aria-disabled="true" aria-describedby="life-event-type-delete-reason" class="shrink-0">
              {{ __('Delete this type') }}
            </x-button.secondary>
          </div>
        </x-panel>
      @endif
    </x-page-section>
  @endif
</x-settings.page>
