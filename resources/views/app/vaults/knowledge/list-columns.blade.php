{{-- The columns of one Knowledge list, to add, rename, reorder or remove. --}}
{{-- @var \App\ViewModels\Vaults\KnowledgeListColumnsViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $item = 'flex w-full cursor-pointer items-center gap-2.5 min-h-8 rounded-md px-2.5 text-left font-sans text-base no-underline transition-colors hover:bg-hover focus-ring-inset';
@endphp

<x-shell :title="__('Columns of :title', ['title' => $viewModel->title()])" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="[
      ['label' => __('Knowledge'), 'url' => route('knowledge.index', $vault)],
      ['label' => $viewModel->title(), 'url' => $viewModel->listUrl()],
      ['label' => __('Columns')],
    ]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="knowledge" />
  </x-slot:sidebar>

  <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[17rem_minmax(0,1fr)]">
    @include('app.vaults.knowledge._folders', ['tree' => $viewModel->tree()])

    <section aria-labelledby="columns-title" class="mx-auto flex w-full max-w-190 min-w-0 flex-col gap-5">
      <x-page-header :title="__('Columns of :title', ['title' => $viewModel->title()])" :description="__('Every item of the list holds one text per column. Removing a column deletes what was written in it.')">
        <x-slot:actions>
          <x-button.secondary :href="$viewModel->listUrl()">{{ __('Back to the list') }}</x-button.secondary>
        </x-slot:actions>
      </x-page-header>

      @error('column')
        <p role="alert" class="text-sm text-error">{{ $message }}</p>
      @enderror

      <ol class="overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel">
        @foreach ($viewModel->columns() as $column)
          <li class="flex items-center gap-2 border-b border-hairline-soft px-3 py-2 last:border-b-0">
            <x-form method="put" :action="$column['updateUrl']" class="flex min-w-0 flex-1 items-center gap-2">
              <input
                name="name"
                value="{{ $column['name'] }}"
                maxlength="255"
                required
                aria-label="{{ __('Name of the column :name', ['name' => $column['name']]) }}"
                class="h-8 min-w-0 flex-1 rounded-md border border-hairline-strong bg-input px-2.5 text-sm text-ink focus-ring-field"
              />
              <x-button.secondary size="small">{{ __('Rename') }}</x-button.secondary>
            </x-form>

            <x-actions-menu :label="__('Actions for the column :name', ['name' => $column['name']])" variant="ghost" width="wide">
              @unless ($column['isFirst'])
                <x-form method="put" :action="$column['positionUrl']">
                  <input type="hidden" name="position" value="{{ $column['position'] - 1 }}" />
                  <button type="submit" role="menuitem" class="{{ $item }} text-ink">{{ __('Move before :other', ['other' => $column['previous']]) }}</button>
                </x-form>
              @endunless

              @unless ($column['isLast'])
                <x-form method="put" :action="$column['positionUrl']">
                  <input type="hidden" name="position" value="{{ $column['position'] + 1 }}" />
                  <button type="submit" role="menuitem" class="{{ $item }} text-ink">{{ __('Move after :other', ['other' => $column['next']]) }}</button>
                </x-form>
              @endunless

              @if ($column['canDelete'])
                <x-modal.warning :title="$column['deleteTitle']" :confirm="__('Remove the column')" :action="$column['destroyUrl']">
                  <x-slot:trigger>
                    <button type="button" role="menuitem" @click="open = true; menuOpen = false" class="{{ $item }} text-error">{{ __('Remove') }}</button>
                  </x-slot:trigger>

                  <p>{{ __('What every item holds in this column is deleted with it, and so is an item holding nothing in any other column. This cannot be undone.') }}</p>
                </x-modal.warning>
              @else
                <p id="knowledge-column-{{ $column['id'] }}-no-delete" class="px-2.5 py-1.5 text-xs text-pretty text-muted">{{ $column['cannotDeleteReason'] }}</p>

                <button
                  type="button"
                  role="menuitem"
                  aria-disabled="true"
                  aria-describedby="knowledge-column-{{ $column['id'] }}-no-delete"
                  @click.prevent
                  class="{{ $item }} cursor-not-allowed text-muted-soft hover:bg-transparent"
                >{{ __('Remove') }}</button>
              @endif
            </x-actions-menu>
          </li>
        @endforeach
      </ol>

      <x-form method="post" :action="$viewModel->createUrl()" class="flex flex-wrap items-end gap-2">
        <div class="min-w-55 flex-1">
          <x-input id="knowledge-new-column" name="name" :label="__('New column')" required :placeholder="__('City, cuisine, rating')" :error="$errors->get('name')" />
        </div>
        <x-button>{{ __('Add the column') }}</x-button>
      </x-form>
    </section>
  </div>
</x-shell>
