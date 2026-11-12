{{-- The form that adds a possession to a vault or edits one: what it is, its fields, where it is kept. --}}
{{-- @var \App\ViewModels\Vaults\PossessionFormViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $trail = $viewModel->isEditing()
    ? [['label' => __('Possessions'), 'url' => $viewModel->indexUrl()], ['label' => $viewModel->possessionName(), 'url' => $viewModel->possessionUrl()], ['label' => __('Edit')]]
    : [['label' => __('Possessions'), 'url' => $viewModel->indexUrl()], ['label' => __('New possession')]];
  $chosenType = collect($viewModel->types())->firstWhere('uuid', $viewModel->chosenType());
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="$trail" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="possessions" />
  </x-slot:sidebar>

  @if ($viewModel->isEditing())
    <x-page-header :title="$viewModel->title()" />
  @else
    <x-page-header
      :title="$viewModel->title()"
      :description="__('Something you own: what it is, where it is kept and what it is worth. Only the name and the type are needed now.')"
    />
  @endif

  <x-form id="possession-form" :method="$viewModel->formMethod()" :action="$viewModel->formAction()" class="flex max-w-180 flex-col gap-8">
    @include('app.vaults.possessions._photos')

    <div class="flex flex-col gap-5">
      <x-input
        id="name"
        :value="$viewModel->name()"
        :label="__('Name')"
        :placeholder="__('Peugeot 308 SW, grandmother’s watch, the good camera')"
        :error="$errors->get('name')"
        maxlength="255"
        autofocus
        required
        required-mark
      />

      <x-textarea
        id="description"
        :value="$viewModel->description()"
        :label="__('Description')"
        :placeholder="__('Anything worth remembering about it')"
        :error="$errors->get('description')"
        maxlength="2000"
        optional
      />
    </div>

    <x-page-section :title="__('Type and its fields')">
      @if ($viewModel->isEditing())
        <x-panel :flush="true" :clip="false">
          <div class="flex items-center gap-2.5 p-4">
            <span class="flex size-8 flex-none items-center justify-center rounded-md bg-hover" aria-hidden="true">
              <span class="size-3 rounded-full" style="background: {{ $chosenType['colour'] }}"></span>
            </span>
            <span class="flex min-w-0 flex-1 flex-col">
              <span class="text-xs text-muted">{{ __('Type') }}</span>
              <span class="truncate text-sm font-semibold text-ink">{{ $chosenType['name'] }}</span>
            </span>
            <span class="text-xs text-muted">{{ __('The type of a possession cannot be changed.') }}</span>
          </div>

          <div class="rounded-b-lg border-t border-hairline bg-card">
            @include('app.vaults.possessions._type-fields', ['fieldsBlock' => $viewModel->fieldsBlock()])
          </div>
        </x-panel>
      @else
        @include('app.vaults.possessions._type-picker', ['viewModel' => $viewModel])
      @endif
    </x-page-section>

    @include('app.vaults.possessions._location', ['viewModel' => $viewModel])

    @include('app.vaults.possessions._declared-value', ['viewModel' => $viewModel])

    @include('app.vaults.possessions._tags', ['viewModel' => $viewModel])

    <x-panel>
      <x-checkbox id="is_featured" :checked="$viewModel->isFeatured()" aria-describedby="is-featured-line">
        <span class="font-semibold text-ink">{{ __('Feature this possession') }}</span>
        <span id="is-featured-line" class="block text-sm text-muted">{{ $viewModel->featuredLine() }}</span>
      </x-checkbox>
    </x-panel>

    @if ($viewModel->isEditing())
      <div class="flex flex-wrap items-center justify-end gap-2.5 border-t border-hairline pt-5">
        <x-button.secondary :href="$viewModel->cancelUrl()" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>
        <x-button type="submit" form="possession-form">{{ __('Save changes') }}</x-button>
      </div>
    @else
      <div
        x-data="{ typeName: @js($chosenType['name'] ?? '') }"
        @possession-type-chosen.window="typeName = $event.detail.name"
        class="flex flex-wrap items-center justify-between gap-3 border-t border-hairline pt-5"
      >
        <p class="text-sm text-muted" aria-live="polite">
          <span x-show="typeName === ''">{{ __('Only the name and the type are required') }}</span>
          <span x-show="typeName !== ''" x-cloak x-text="@js(__('New :type', ['type' => '__TYPE__'])).replace('__TYPE__', typeName)"></span>
        </p>

        <div class="flex flex-wrap items-center gap-2.5">
          <x-button.secondary :href="$viewModel->cancelUrl()" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>
          <x-button type="submit" form="possession-form">{{ __('Add possession') }}</x-button>
        </div>
      </div>
    @endif
  </x-form>
</x-shell>
