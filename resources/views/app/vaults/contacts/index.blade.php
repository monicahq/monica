{{--
  Everybody in one vault, on one list.

  The screen is as tall as the window and the rows are what scrolls, so the
  header of the table stays where it is and somebody thirty rows down still knows
  which column they are reading.

  Searching, narrowing and sorting all go through the URL and redraw the region
  below the bar over ajax. That means a narrowed list can be linked to and comes
  back the same after a reload, which a table that only ever changed in the
  browser could not offer.

  Which columns are drawn is the one thing that does not go through the URL. It
  is a preference about looking rather than a question about the data, the same
  rows are sent either way, so it lives in the browser and hides a column by not
  drawing it.

  @var \App\ViewModels\Vaults\ContactsViewModel $viewModel
--}}
@php
  $chip = 'rounded-full border px-3 py-1 text-[13px] transition-colors duration-150';
  $columnCount = count($viewModel->columns());
@endphp

<x-app-layout wide :title="$viewModel->title()">
  <x-slot:sidebar>
    <x-vault.sidebar
      :vault="$viewModel->vault()"
      :vaults="$viewModel->vaults()"
      :user="$viewModel->user()"
      current="contacts"
    />
  </x-slot:sidebar>

  <x-slot:breadcrumb>
    <x-breadcrumb
      :items="[
        ['label' => $viewModel->vault()->name, 'url' => $viewModel->vaultUrl()],
        ['label' => __('Contacts')],
      ]"
    />
  </x-slot:breadcrumb>

  <x-slot:actions>
    <x-button :href="$viewModel->newContactUrl()" size="small" data-turbo="true">{{ __('Add contact') }}</x-button>
  </x-slot:actions>

  <div
    id="contacts"
    x-merge="replace"
    x-data="hiddenColumns('contacts.hidden-columns')"
    class="flex min-h-0 flex-1 flex-col gap-3.5"
  >
    <div class="flex flex-wrap items-end gap-3 px-4 sm:px-7">
      <div class="min-w-0 space-y-0.5">
        <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Contacts') }}</h1>

        <p class="text-[13px] text-muted">{{ $viewModel->resultLine() }}</p>
      </div>

      <div class="flex flex-wrap items-center gap-2.5 sm:ml-auto">
        <form method="get" action="{{ $viewModel->searchUrl() }}" x-target="contacts" class="flex items-center gap-2">
          <input type="hidden" name="sort" value="{{ $viewModel->sort() }}" />
          <input type="hidden" name="direction" value="{{ $viewModel->direction() }}" />

          <x-input
            type="search"
            id="query"
            size="small"
            :value="$viewModel->query()"
            :placeholder="__('Search names and nicknames')"
            :aria-label="__('Search the contacts')"
            class="w-56"
          />

          <x-button.secondary size="small">{{ __('Search') }}</x-button.secondary>
        </form>

        <div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative">
          <button
            type="button"
            @click="open = ! open"
            @click.outside="open = false"
            :aria-expanded="open ? 'true' : 'false'"
            :class="open ? 'bg-hover' : 'bg-card'"
            class="flex cursor-pointer items-center gap-2 rounded-md border border-hairline-strong px-3 py-1.5 text-[13px] font-medium text-ink transition-colors duration-150 hover:bg-hover"
          >
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" aria-hidden="true">
              <line x1="2.4" y1="4.4" x2="13.6" y2="4.4"></line>
              <line x1="2.4" y1="8" x2="13.6" y2="8"></line>
              <line x1="2.4" y1="11.6" x2="13.6" y2="11.6"></line>
            </svg>

            {{ __('Columns') }}

            <span class="font-mono text-xs text-muted-soft"><span x-text="{{ $columnCount }} - hidden.length"></span>/{{ $columnCount }}</span>
          </button>

          <div
            x-cloak
            x-show="open"
            x-transition.opacity.duration.120ms
            class="absolute top-full right-0 z-20 mt-1.5 w-68 rounded-lg border border-hairline-strong bg-canvas p-1.5 shadow-lg"
          >
            <div class="flex items-center gap-2.5 px-2 pt-1 pb-1.5">
              <span class="text-xs tracking-widest text-muted-soft uppercase">{{ __('Columns') }}</span>

              <button type="button" @click="reset()" class="ml-auto cursor-pointer text-[13px] text-brand hover:underline">{{ __('Reset') }}</button>
            </div>

            @foreach ($viewModel->columns() as $column)
              @if ($column['isLocked'])
                <span class="flex items-center gap-2.5 rounded-md px-2 py-1.5 text-sm text-muted">
                  <span class="flex size-4 shrink-0 items-center justify-center rounded-sm border border-primary bg-primary text-on-primary" aria-hidden="true">
                    <svg width="10" height="10" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M2.5 6.3 4.8 8.6 9.5 3.8"></path>
                    </svg>
                  </span>

                  {{ $column['label'] }}

                  <span class="ml-auto text-xs text-muted-soft">{{ __('always') }}</span>
                </span>
              @else
                <button
                  type="button"
                  role="checkbox"
                  @click="toggle('{{ $column['key'] }}')"
                  :aria-checked="shown('{{ $column['key'] }}') ? 'true' : 'false'"
                  class="flex w-full cursor-pointer items-center gap-2.5 rounded-md px-2 py-1.5 text-left text-sm text-ink transition-colors hover:bg-hover"
                >
                  <span
                    :class="shown('{{ $column['key'] }}') ? 'border-primary bg-primary text-on-primary' : 'border-hairline-strong bg-input text-transparent'"
                    class="flex size-4 shrink-0 items-center justify-center rounded-sm border"
                    aria-hidden="true"
                  >
                    <svg width="10" height="10" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M2.5 6.3 4.8 8.6 9.5 3.8"></path>
                    </svg>
                  </span>

                  {{ $column['label'] }}
                </button>
              @endif
            @endforeach
          </div>
        </div>
      </div>
    </div>

    <div class="flex flex-wrap items-center gap-2 px-4 sm:px-7">
      @foreach ($viewModel->filters() as $filter)
        <a
          href="{{ $filter['url'] }}"
          x-target="contacts"
          @if ($filter['isCurrent']) aria-current="true" @endif
          class="{{ $chip }} {{ $filter['isCurrent'] ? 'border-primary bg-sunken font-semibold text-ink' : 'border-hairline-strong bg-canvas text-body hover:bg-hover' }}"
        >{{ $filter['label'] }}</a>
      @endforeach

      <span class="ml-auto text-[13px] text-muted-soft">{{ $viewModel->sortLine() }}</span>
    </div>

    <div class="min-h-0 flex-1 overflow-auto border-t border-hairline bg-canvas">
      @if ($viewModel->vaultIsEmpty())
        <x-empty-state
          :title="__('Nobody here yet')"
          :href="$viewModel->newContactUrl()"
          :link-label="__('Write somebody down')"
        >
          <x-slot:icon>
            <x-entity-icon name="profile" :size="20" />
          </x-slot:icon>

          {{ $viewModel->emptyLine() }}
        </x-empty-state>
      @elseif ($viewModel->noResults())
        <div class="grid justify-items-center gap-1 px-6 py-15 text-center">
          <p class="text-sm font-semibold text-ink">{{ __('Nothing matches') }}</p>

          <p class="text-sm text-muted">{{ __('Try a shorter search, or another filter above.') }}</p>
        </div>
      @else
        <table class="w-full border-separate border-spacing-0">
          <thead>
            <tr>
              @foreach ($viewModel->columns() as $column)
                <th
                  scope="col"
                  @unless ($column['isLocked']) x-show="shown('{{ $column['key'] }}')" @endunless
                  @class([
                    'sticky top-0 z-2 border-b border-hairline bg-canvas p-0 font-normal whitespace-nowrap',
                    'w-full' => $loop->first,
                    'text-right' => $column['alignRight'],
                    'text-left' => ! $column['alignRight'],
                  ])
                >
                  @if ($column['isSortable'])
                    <a
                      href="{{ $column['url'] }}"
                      x-target="contacts"
                      title="{{ $column['title'] }}"
                      @class([
                        'flex items-center gap-1.5 px-3.5 py-2.5 text-[13px] transition-colors duration-150',
                        'justify-end' => $column['alignRight'],
                        'font-semibold text-ink' => $column['isSorted'],
                        'text-muted hover:text-ink' => ! $column['isSorted'],
                      ])
                    >
                      {{ $column['label'] }}

                      <svg
                        width="12"
                        height="12"
                        viewBox="0 0 16 16"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        @class([
                          'shrink-0',
                          'text-transparent' => ! $column['isSorted'],
                          'rotate-180' => $column['isAscending'],
                        ])
                        aria-hidden="true"
                      >
                        <path d="M4 6.5 8 10.5 12 6.5"></path>
                      </svg>
                    </a>
                  @else
                    <span class="block px-3.5 py-2.5 text-[13px] text-muted" title="{{ $column['title'] }}">{{ $column['label'] }}</span>
                  @endif
                </th>
              @endforeach
            </tr>
          </thead>

          <tbody id="contact-rows" x-merge="append">
            @foreach ($viewModel->rows() as $row)
              @include('app.vaults.contacts._row', ['row' => $row])
            @endforeach
          </tbody>
        </table>

        <div id="pagination" class="flex items-center justify-center px-6 pt-5 pb-11">
          @if ($viewModel->hasMorePages())
            <x-link x-target="contact-rows pagination" :href="$viewModel->nextPageUrl()" class="text-[13px]">{{ __('Load more') }}</x-link>
          @else
            <span class="text-[13px] text-muted-soft">{{ $viewModel->endLine() }}</span>
          @endif
        </div>
      @endif
    </div>
  </div>
</x-app-layout>
