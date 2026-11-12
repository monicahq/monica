{{-- The column of folders beside every Knowledge screen, and the way to add a note, a list or a folder. --}}
{{-- @var \App\ViewModels\Vaults\KnowledgeFolderTreeBlock $tree --}}
@php
  $folders = $tree->folders();
  $target = $tree->targetFolder();
  $item = 'flex w-full cursor-pointer items-center gap-2.5 min-h-8 rounded-md px-2.5 text-left font-sans text-base no-underline transition-colors hover:bg-hover focus-ring-inset';
  $entry = 'focus-ring flex min-h-8 items-center gap-2 rounded-md px-2 py-1.5 text-nav no-underline transition duration-(--duration-fast) ease-out';
  $entryIdle = 'text-body hover:bg-hover hover:text-ink';
  $entryCurrent = 'bg-card font-medium text-ink shadow-nav-active';
@endphp

<nav aria-label="{{ __('Folders, notes and lists') }}" class="flex flex-col gap-2 rounded-lg border border-hairline bg-canvas p-2 shadow-panel">
  <div class="flex items-center gap-2 ps-2 pt-1">
    <span class="flex text-ink" aria-hidden="true"><x-icon name="journal" :size="16" /></span>
    <h2 class="flex-1 text-base font-semibold text-ink">{{ __('Knowledge') }}</h2>

    <x-actions-menu :label="__('Add to Knowledge')" variant="ghost" width="wide">
      <x-slot:glyph>
        <x-icon name="plus" :size="16" />
      </x-slot:glyph>

      <x-form method="post" :action="$tree->newNoteUrl()">
        @if ($target !== null)
          <input type="hidden" name="folder" value="{{ $target->uuid }}" />
        @endif
        <button type="submit" role="menuitem" class="{{ $item }} text-ink">
          <x-icon name="note" :size="15" />
          {{ __('New note') }}
        </button>
      </x-form>

      <x-form method="post" :action="$tree->newListUrl()">
        @if ($target !== null)
          <input type="hidden" name="folder" value="{{ $target->uuid }}" />
        @endif
        <button type="submit" role="menuitem" class="{{ $item }} text-ink">
          <x-icon name="text-lines" :size="15" />
          {{ __('New list') }}
        </button>
      </x-form>

      <div class="my-1 h-px bg-hairline" role="separator"></div>

      <x-modal.form :title="__('New folder')" :confirm="__('Add the folder')" method="post" :action="$tree->newFolderUrl()">
        <x-slot:trigger>
          <button type="button" role="menuitem" @click="open = true; menuOpen = false" class="{{ $item }} text-ink">
            <x-icon name="folder" :size="15" />
            {{ __('New folder') }}
          </button>
        </x-slot:trigger>

        <x-input id="knowledge-folder-name" name="name" :label="__('Name')" required :placeholder="__('Home, Travel, Health')" />
      </x-modal.form>

      <p class="px-2.5 py-1.5 text-xs text-muted">{{ $tree->targetLine() }}</p>
    </x-actions-menu>
  </div>

  <form method="get" action="{{ $tree->searchUrl() }}" role="search" class="px-1">
    <label class="flex h-8 items-center gap-2 rounded-md border border-hairline bg-canvas px-2.5 focus-within:border-ink">
      <span class="flex text-muted" aria-hidden="true"><x-icon name="search" :size="15" /></span>
      <input
        type="search"
        name="search"
        value="{{ $tree->search() }}"
        placeholder="{{ __('Search notes and lists') }}"
        aria-label="{{ __('Search notes and lists') }}"
        class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-ink outline-none focus:ring-0"
      />
    </label>
  </form>

  @error('folder')
    <p role="alert" class="px-2 text-xs text-pretty text-error">{{ $message }}</p>
  @enderror

  <div class="flex flex-col gap-px pb-1">
    @if ($tree->hasFolders())
      <a
        href="{{ $tree->contentsUrl() }}"
        @if ($tree->contentsIsCurrent() && ! $tree->isSearching()) aria-current="page" @endif
        class="{{ $entry }} {{ $tree->contentsIsCurrent() && ! $tree->isSearching() ? $entryCurrent : $entryIdle }}"
      >
        <span class="flex shrink-0 text-muted" aria-hidden="true"><x-icon name="layers" :size="15" /></span>
        <span class="min-w-0 flex-1">{{ __('Contents') }}</span>
      </a>
    @else
      <p class="px-2 py-2 text-sm text-pretty text-muted">{{ __('No folders yet. Your folders, notes and lists will be listed here.') }}</p>
    @endif

    @foreach ($folders as $folder)
      <div x-data="{ unfolded: true, renaming: false }" class="mt-1 flex flex-col gap-px">
        <x-form
          x-cloak
          x-show="renaming"
          method="put"
          :action="$folder['renameUrl']"
          class="flex items-center ps-7 pe-1"
        >
          <input
            x-ref="name"
            name="name"
            value="{{ $folder['name'] }}"
            aria-label="{{ __('Folder name') }}"
            maxlength="255"
            @keydown.escape.prevent="$el.value = @js($folder['name']); renaming = false"
            @blur="if (renaming) $el.form.requestSubmit()"
            class="h-8 w-full rounded-md border border-ink bg-input px-2 text-sm text-ink focus-ring-field"
          />
        </x-form>

        <div x-show="! renaming" class="flex items-center gap-0.5">
          <button
            type="button"
            @click="unfolded = ! unfolded"
            @dblclick="renaming = true; $nextTick(() => $refs.name.select())"
            :aria-expanded="unfolded ? 'true' : 'false'"
            class="focus-ring flex min-h-8 min-w-0 flex-1 cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-left text-[13.5px] font-semibold text-ink hover:bg-hover"
          >
            <span class="flex text-muted transition-transform duration-(--duration-fast)" :class="unfolded || 'rotate-[-90deg]'" aria-hidden="true"><x-icon name="chevron-down" :size="13" /></span>
            <span class="flex shrink-0 text-ink-soft" aria-hidden="true"><x-icon name="folder" :size="16" /></span>
            <span class="min-w-0 flex-1 truncate">{{ $folder['name'] }}</span>
            @if ($folder['count'] > 0)
              <span class="text-xs font-normal text-muted">{{ $folder['count'] }}</span>
            @endif
          </button>

          <x-actions-menu :label="__('Actions for :name', ['name' => $folder['name']])" variant="ghost" width="wide">
            <button type="button" role="menuitem" @click="menuOpen = false; renaming = true; $nextTick(() => $refs.name.select())" class="{{ $item }} text-ink">
              {{ __('Rename') }}
            </button>

            @unless ($folder['isFirst'])
              <x-form method="put" :action="$folder['positionUrl']">
                <input type="hidden" name="position" value="{{ $folder['position'] - 1 }}" />
                <button type="submit" role="menuitem" class="{{ $item }} text-ink">{{ __('Move above :other', ['other' => $folder['previous']]) }}</button>
              </x-form>
            @endunless

            @unless ($folder['isLast'])
              <x-form method="put" :action="$folder['positionUrl']">
                <input type="hidden" name="position" value="{{ $folder['position'] + 1 }}" />
                <button type="submit" role="menuitem" class="{{ $item }} text-ink">{{ __('Move below :other', ['other' => $folder['next']]) }}</button>
              </x-form>
            @endunless

            @if ($folder['canDelete'])
              <x-modal.form :title="$folder['deleteTitle']" :confirm="__('Delete the folder')" method="delete" :action="$folder['destroyUrl']">
                <x-slot:trigger>
                  <button type="button" role="menuitem" @click="open = true; menuOpen = false" class="{{ $item }} text-error">
                    {{ __('Delete folder') }}
                  </button>
                </x-slot:trigger>

                @if ($folder['documentCount'] > 0)
                  <x-select id="knowledge-destination-{{ $folder['id'] }}" name="destination" :label="__('Its notes and lists')">
                    @foreach ($folder['destinations'] as $destination)
                      <option value="{{ $destination['id'] }}">{{ __('Move them to :name', ['name' => $destination['name']]) }}</option>
                    @endforeach
                    <option value="delete">{{ __('Delete them too') }}</option>
                  </x-select>
                @else
                  <input type="hidden" name="destination" value="delete" />
                @endif

                <p class="text-sm text-pretty text-body">{{ $folder['deleteConsequence'] }}</p>
              </x-modal.form>
            @else
              <p id="knowledge-folder-{{ $folder['id'] }}-no-delete" class="px-2.5 py-1.5 text-xs text-pretty text-muted">{{ $folder['cannotDeleteReason'] }}</p>

              <button
                type="button"
                role="menuitem"
                aria-disabled="true"
                aria-describedby="knowledge-folder-{{ $folder['id'] }}-no-delete"
                @click.prevent
                class="{{ $item }} cursor-not-allowed text-muted-soft hover:bg-transparent"
              >{{ __('Delete folder') }}</button>
            @endif
          </x-actions-menu>
        </div>

        <div x-show="unfolded" class="flex flex-col gap-px">
          @foreach ($folder['documents'] as $document)
            <a
              href="{{ $document['url'] }}"
              @if ($document['isCurrent']) aria-current="page" @endif
              class="{{ $entry }} ps-7.5 {{ $document['isCurrent'] ? $entryCurrent : $entryIdle }}"
            >
              <span class="flex shrink-0 text-muted" aria-hidden="true"><x-icon :name="$document['icon']" :size="15" /></span>
              <span class="min-w-0 flex-1 truncate">{{ $document['title'] }}</span>
            </a>
          @endforeach

          @if ($folder['isEmpty'] && ! $tree->isSearching())
            <p class="py-1.5 ps-13.5 pe-2 text-xs text-muted">{{ __('Empty. Add a note or a list with the plus above.') }}</p>
          @endif
        </div>
      </div>
    @endforeach

    @if ($tree->isSearching() && $folders === [])
      <p class="px-2 py-3 text-sm text-pretty text-muted">{{ __('Nothing matches “:words” in titles or text.', ['words' => $tree->search()]) }}</p>
    @endif
  </div>
</nav>
