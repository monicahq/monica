{{-- One list of a vault's Knowledge: its description, a table of its items, and the ways to change them. --}}
{{-- @var \App\ViewModels\Vaults\KnowledgeListViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $columns = $viewModel->columns();
  $items = $viewModel->items();
  $inline = '[&_strong]:font-semibold [&_strong]:text-ink [&_code]:rounded-xs [&_code]:bg-card [&_code]:px-1 [&_code]:font-mono [&_code]:text-xs [&_a]:rounded-xs [&_a]:text-ink [&_a]:underline [&_a]:decoration-hairline-strong [&_a]:underline-offset-3 [&_a:hover]:decoration-ink';
  $startsAdding = $viewModel->startsAdding() || ($errors->any() && old('cells') !== null && ! collect($items)->contains('isEditing', true));
@endphp

<x-shell :title="$viewModel->title()" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="[
      ['label' => __('Knowledge'), 'url' => route('knowledge.index', $vault)],
      ['label' => $viewModel->folderName()],
      ['label' => $viewModel->title()],
    ]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="knowledge" />
  </x-slot:sidebar>

  <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[17rem_minmax(0,1fr)]">
    @include('app.vaults.knowledge._folders', ['tree' => $viewModel->tree()])

    <section aria-labelledby="list-title" class="mx-auto flex w-full max-w-190 min-w-0 flex-col gap-5">
      @if ($viewModel->isEditing())
        <h1 id="list-title" class="sr-only">{{ __('Edit the list “:title”', ['title' => $viewModel->title()]) }}</h1>

        @include('app.vaults.knowledge._list-form', ['viewModel' => $viewModel])
      @else
        <header class="flex flex-wrap items-start gap-x-4 gap-y-3">
          <div class="flex min-w-60 flex-1 flex-col gap-2">
            <p class="flex items-center gap-1.5 text-sm text-muted">
              <x-icon name="folder" :size="14" />
              <span>{{ $viewModel->folderName() }}</span>
              <span class="text-muted-soft" aria-hidden="true">·</span>
              <span>{{ __('List') }}</span>
            </p>
            <h1 id="list-title" class="text-page-title text-ink">{{ $viewModel->title() }}</h1>
            <p class="text-xs text-muted"><x-timestamp :at="$viewModel->editedAt()">{{ $viewModel->edited() }}</x-timestamp> · {{ $viewModel->itemCount() }}</p>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <x-button.secondary size="small" :href="$viewModel->editUrl()">{{ __('Edit') }}</x-button.secondary>
            <x-button.secondary size="small" :href="$viewModel->columnsUrl()">{{ __('Edit columns') }}</x-button.secondary>

            <x-delete-action
              :title="$viewModel->deleteTitle()"
              :confirm="__('Delete the list')"
              :action="$viewModel->destroyUrl()"
              :label="__('Delete')"
              variant="ghost"
              size="small"
            >
              <p>{{ __('The list is deleted for good, with its columns and every item in it.') }}</p>
            </x-delete-action>
          </div>
        </header>

        @if ($viewModel->description() !== '')
          <p class="max-w-[68ch] text-base leading-[1.65] text-pretty text-body {{ $inline }}">{!! $viewModel->descriptionHtml() !!}</p>
        @endif

        <div x-data="{ adding: @js($startsAdding) }" class="overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel">
          <div class="overflow-x-auto">
            <table class="w-full border-collapse text-[13.5px]">
              <thead>
                <tr>
                  @foreach ($columns as $column)
                    <th scope="col" class="border-b border-hairline px-4 py-2.5 text-start text-xs font-medium whitespace-nowrap text-muted">{{ $column['name'] }}</th>
                  @endforeach
                  <th scope="col" class="w-px border-b border-hairline px-2 py-2.5"><span class="sr-only">{{ __('Actions') }}</span></th>
                </tr>
              </thead>
              <tbody>
                @foreach ($items as $item)
                  @include('app.vaults.knowledge._item-row', ['item' => $item, 'inline' => $inline])
                @endforeach
              </tbody>
            </table>
          </div>

          <x-form x-cloak x-show="adding" method="post" :action="$viewModel->addItemUrl()" class="flex flex-col gap-3 border-t border-hairline-soft p-3" aria-label="{{ __('Add an item') }}">
            <div class="grid grid-cols-[repeat(auto-fit,minmax(8rem,1fr))] gap-2">
              @foreach ($columns as $index => $column)
                <input
                  name="cells[{{ $column['id'] }}]"
                  value="{{ old("cells.{$column['id']}") }}"
                  placeholder="{{ $column['name'] }}"
                  aria-label="{{ $column['name'] }}"
                  maxlength="5000"
                  @if ($index === 0) x-ref="firstCell" @endif
                  @if ($errors->has("cells.{$column['id']}")) aria-invalid="true" aria-describedby="knowledge-cells-error" @endif
                  @keydown.escape.prevent="adding = false"
                  class="h-8 w-full rounded-md border border-hairline-strong bg-input px-2.5 text-[13px] text-ink focus-ring-field"
                />
              @endforeach
            </div>
            <x-error id="knowledge-cells-error" :messages="$errors->first('cells*')" />
            <div class="flex flex-wrap items-center gap-2">
              <x-button size="small">{{ __('Add item') }}</x-button>
              <x-button variant="ghost" size="small" type="button" @click="adding = false">{{ __('Cancel') }}</x-button>
              <span class="ms-auto text-xs text-muted">{{ __('Cells take Markdown: **bold** and web addresses.') }}</span>
            </div>
          </x-form>

          <div x-show="! adding" class="flex flex-wrap items-center gap-2 border-t border-hairline-soft bg-card px-3 py-2">
            <x-button variant="ghost" size="small" type="button" @click="adding = true; $nextTick(() => $refs.firstCell?.focus())">
              <x-slot:icon><x-icon name="plus" :size="16" /></x-slot:icon>
              {{ __('Add an item') }}
            </x-button>
            <span class="ms-auto text-xs text-muted">{{ $viewModel->itemCount() }}</span>
          </div>
        </div>
      @endif
    </section>
  </div>
</x-shell>
